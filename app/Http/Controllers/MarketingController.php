<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignDelivery;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CustomerSegment;
use App\Models\SystemAudit;
use App\Services\Marketing\CampaignService;
use App\Services\Marketing\CouponService;
use App\Services\Marketing\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingController extends Controller
{
    public function index()
    {
        $segments = CustomerSegment::withCount('clients')->latest()->get();
        $campaigns = Campaign::withCount('deliveries')->latest()->limit(25)->get();
        $coupons = Coupon::latest()->get();
        $clients = Client::with('loyaltyAccount')->orderBy('name')->get();
        $metrics = ['clients' => Client::count(), 'repeat_clients' => Client::has('orders', '>=', 2)->count(), 'campaign_deliveries' => CampaignDelivery::where('status', 'simulated')->count(), 'coupon_redemptions' => CouponRedemption::count()];
        $sources = Client::selectRaw('source, COUNT(*) as total')->groupBy('source')->pluck('total', 'source');

        return view('marketing.index', compact('segments', 'campaigns', 'coupons', 'clients', 'metrics', 'sources'));
    }

    public function storeSegment(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:customer_segments'], 'description' => ['nullable', 'string', 'max:500'], 'client_ids' => ['nullable', 'array'], 'client_ids.*' => ['integer', 'exists:clients,id']]);
        $segment = CustomerSegment::create($data);
        $segment->clients()->sync($data['client_ids'] ?? []);

        return back()->with('success', 'Segmento creado.');
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])], 'subject' => ['nullable', 'string', 'max:160'], 'message' => ['required', 'string', 'max:2000'], 'customer_segment_id' => ['nullable', 'exists:customer_segments,id']]);
        Campaign::create([...$data, 'created_by' => $request->user()->id]);

        return back()->with('success', 'Campaña guardada como borrador.');
    }

    public function simulateCampaign(Request $request, Campaign $campaign, CampaignService $service)
    {
        $counts = $service->simulate($campaign);
        SystemAudit::create(['user_id' => $request->user()->id, 'action' => 'campaign.simulate', 'target' => 'campaign:'.$campaign->id, 'status' => 'success', 'metadata' => $counts]);

        return back()->with('success', "Simulación terminada: {$counts['simulated']} aptos, {$counts['skipped']} omitidos.");
    }

    public function storeCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'alpha_dash', 'max:40', 'unique:coupons'], 'name' => ['required', 'string', 'max:120'], 'discount_type' => ['required', Rule::in(['fixed', 'percentage'])], 'discount_value' => ['required', 'numeric', 'gt:0'], 'minimum_amount' => ['nullable', 'numeric', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'per_client_limit' => ['required', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at']]);
        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);

        return back()->with('success', 'Cupón creado.');
    }

    public function redeemCoupon(Request $request, CouponService $service)
    {
        $data = $request->validate([
            'coupon_id' => ['required', 'exists:coupons,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        try {
            $redemption = $service->redeem(Coupon::findOrFail($data['coupon_id']), Client::findOrFail($data['client_id']), (float) $data['amount'], $data['idempotency_key']);
        } catch (\DomainException $exception) {
            return back()->withErrors(['coupon' => $exception->getMessage()])->withInput();
        }

        SystemAudit::create(['user_id' => $request->user()->id, 'action' => 'coupon.redeem', 'target' => 'coupon_redemption:'.$redemption->id, 'status' => 'success', 'metadata' => ['client_id' => $redemption->client_id, 'discount_amount' => $redemption->discount_amount]]);

        return back()->with('success', 'Cupón canjeado. Descuento calculado: S/ '.number_format((float) $redemption->discount_amount, 2));
    }

    public function adjustLoyalty(Request $request, LoyaltyService $service)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'type' => ['required', Rule::in(['earn', 'redeem', 'adjust'])],
            'points' => ['required', 'integer', 'not_in:0', Rule::when($request->input('type') !== 'adjust', ['min:1'])],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        try {
            $transaction = $service->apply(Client::findOrFail($data['client_id']), (int) $data['points'], $data['type'], $data['idempotency_key'], userId: $request->user()->id);
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return back()->withErrors(['loyalty' => $exception->getMessage()])->withInput();
        }

        SystemAudit::create(['user_id' => $request->user()->id, 'action' => 'loyalty.'.$data['type'], 'target' => 'loyalty_transaction:'.$transaction->id, 'status' => 'success', 'metadata' => ['client_id' => $data['client_id'], 'points' => $transaction->points]]);

        return back()->with('success', 'Movimiento de puntos registrado.');
    }
}
