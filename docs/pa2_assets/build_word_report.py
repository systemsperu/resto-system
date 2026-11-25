from pathlib import Path
from docx import Document
from docx.enum.section import WD_ORIENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.shared import Cm, Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn


ROOT = Path(__file__).resolve().parents[2]
ASSETS = ROOT / "docs" / "pa2_assets"
OUTPUT = ROOT / "docs" / "RESTO_SYSTEM_PA2_Informe_Tecnico.docx"

AZUL = "16324F"
AZUL_CLARO = "DCECFB"
CELESTE = "EAF3FA"
VERDE = "EAF7F0"
NARANJA = "FFF4E5"
GRIS = "F2F4F7"
BLANCO = "FFFFFF"


def sombrear(cell, color):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), color)


def margenes_celda(cell, top=70, start=90, bottom=70, end=90):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for nombre, valor in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        nodo = tc_mar.find(qn(f"w:{nombre}"))
        if nodo is None:
            nodo = OxmlElement(f"w:{nombre}")
            tc_mar.append(nodo)
        nodo.set(qn("w:w"), str(valor))
        nodo.set(qn("w:type"), "dxa")


def repetir_encabezado(row):
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def evitar_corte_fila(row):
    tr_pr = row._tr.get_or_add_trPr()
    cant_split = OxmlElement("w:cantSplit")
    tr_pr.append(cant_split)


def texto_celda(cell, texto, negrita=False, tamano=8, color=None):
    cell.text = ""
    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(0)
    r = p.add_run(str(texto))
    r.bold = negrita
    r.font.name = "Aptos"
    r.font.size = Pt(tamano)
    if color:
        r.font.color.rgb = RGBColor.from_string(color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    margenes_celda(cell)


def agregar_tabla(doc, encabezados, filas, anchos=None, tamano=7.8):
    tabla = doc.add_table(rows=1, cols=len(encabezados))
    tabla.alignment = WD_TABLE_ALIGNMENT.CENTER
    tabla.style = "Table Grid"
    tabla.autofit = True
    encabezado = tabla.rows[0]
    repetir_encabezado(encabezado)
    for i, titulo in enumerate(encabezados):
        texto_celda(encabezado.cells[i], titulo, True, tamano, BLANCO)
        sombrear(encabezado.cells[i], AZUL)
        if anchos:
            encabezado.cells[i].width = Cm(anchos[i])
    for idx, fila in enumerate(filas):
        row = tabla.add_row()
        evitar_corte_fila(row)
        for i, valor in enumerate(fila):
            texto_celda(row.cells[i], valor, False, tamano)
            if idx % 2 == 1:
                sombrear(row.cells[i], "F8FAFC")
            if anchos:
                row.cells[i].width = Cm(anchos[i])
    doc.add_paragraph().paragraph_format.space_after = Pt(0)
    return tabla


def agregar_codigo(doc, codigo):
    tabla = doc.add_table(rows=1, cols=1)
    tabla.style = "Table Grid"
    celda = tabla.cell(0, 0)
    sombrear(celda, "F5F7FA")
    margenes_celda(celda, 110, 130, 110, 130)
    celda.text = ""
    p = celda.paragraphs[0]
    p.paragraph_format.space_after = Pt(0)
    for i, linea in enumerate(codigo.strip("\n").splitlines()):
        if i:
            p.add_run("\n")
        r = p.add_run(linea)
        r.font.name = "Consolas"
        r.font.size = Pt(8)
        r.font.color.rgb = RGBColor(35, 48, 63)
    doc.add_paragraph().paragraph_format.space_after = Pt(0)


def agregar_parrafo(doc, texto="", estilo=None, negrita_inicial=None):
    p = doc.add_paragraph(style=estilo)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.line_spacing = 1.08
    if negrita_inicial and texto.startswith(negrita_inicial):
        r = p.add_run(negrita_inicial)
        r.bold = True
        p.add_run(texto[len(negrita_inicial):])
    else:
        p.add_run(texto)
    return p


def agregar_lista(doc, elementos, numerada=False):
    for item in elementos:
        estilo = "List Number" if numerada else "List Bullet"
        p = doc.add_paragraph(style=estilo)
        p.paragraph_format.space_after = Pt(3)
        p.add_run(item)


def agregar_figura(doc, archivo, leyenda, ancho=6.7):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(4)
    p.add_run().add_picture(str(ASSETS / archivo), width=Inches(ancho))
    cap = doc.add_paragraph()
    cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    cap.paragraph_format.space_after = Pt(9)
    r = cap.add_run(leyenda)
    r.italic = True
    r.font.size = Pt(9)
    r.font.color.rgb = RGBColor(75, 85, 99)


def campo(p, instruccion):
    run = p.add_run()
    fld_char = OxmlElement("w:fldChar")
    fld_char.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = instruccion
    sep = OxmlElement("w:fldChar")
    sep.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = "Actualizar campo en Word"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([fld_char, instr, sep, text, end])


def configurar_documento(doc):
    sec = doc.sections[0]
    sec.top_margin = Cm(2.0)
    sec.bottom_margin = Cm(1.8)
    sec.left_margin = Cm(2.1)
    sec.right_margin = Cm(2.1)
    sec.header_distance = Cm(0.8)
    sec.footer_distance = Cm(0.8)

    normal = doc.styles["Normal"]
    normal.font.name = "Aptos"
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = RGBColor(31, 41, 55)

    for nombre, tamano, color in (("Title", 30, AZUL), ("Heading 1", 19, AZUL), ("Heading 2", 14, "2563A6"), ("Heading 3", 11.5, "374151")):
        estilo = doc.styles[nombre]
        estilo.font.name = "Aptos Display"
        estilo.font.size = Pt(tamano)
        estilo.font.bold = True
        estilo.font.color.rgb = RGBColor.from_string(color)
        estilo.paragraph_format.space_before = Pt(12)
        estilo.paragraph_format.space_after = Pt(6)
        estilo.paragraph_format.keep_with_next = True

    header = sec.header.paragraphs[0]
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    r = header.add_run("RESTO SYSTEM  |  PA2 Construcción de Software")
    r.font.name = "Aptos"
    r.font.size = Pt(8)
    r.font.color.rgb = RGBColor(100, 116, 139)

    footer = sec.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = footer.add_run("Informe técnico  •  Página ")
    r.font.size = Pt(8)
    campo(footer, "PAGE")

    settings = doc.settings._element
    update_fields = OxmlElement("w:updateFields")
    update_fields.set(qn("w:val"), "true")
    settings.append(update_fields)


def portada(doc):
    for _ in range(3):
        doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("RESTO SYSTEM")
    r.bold = True
    r.font.name = "Aptos Display"
    r.font.size = Pt(34)
    r.font.color.rgb = RGBColor.from_string(AZUL)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Plan de Desarrollo de Software — PA2")
    r.bold = True
    r.font.size = Pt(20)
    r.font.color.rgb = RGBColor(37, 99, 166)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("Informe técnico del estado actual del sistema").font.size = Pt(15)
    doc.add_paragraph()
    tabla = doc.add_table(rows=6, cols=2)
    tabla.alignment = WD_TABLE_ALIGNMENT.CENTER
    tabla.style = "Table Grid"
    datos = [
        ("Curso", "Construcción de Software"),
        ("Entregable", "Plan de Desarrollo de Software — PA2"),
        ("Sistema académico", "RESTO SYSTEM"),
        ("Fecha de revisión", "13 de noviembre de 2026"),
        ("Rama / commit", "develop / e53e6520ec87fd8a74fc52ff5227543e922341d4"),
        ("Alcance", "Login, sesiones, roles y permisos; mesas y camareros; registro de pedidos y menú"),
    ]
    for i, (k, v) in enumerate(datos):
        texto_celda(tabla.cell(i, 0), k, True, 9, AZUL)
        sombrear(tabla.cell(i, 0), AZUL_CLARO)
        texto_celda(tabla.cell(i, 1), v, False, 9)
    doc.add_paragraph()
    aviso = doc.add_table(rows=1, cols=1)
    aviso.style = "Table Grid"
    sombrear(aviso.cell(0, 0), NARANJA)
    texto_celda(aviso.cell(0, 0), "Revisión de solo lectura. No se ejecutaron pruebas, migraciones, seeders ni operaciones externas; no se leyó .env ni se inspeccionaron datos personales.", True, 9)
    doc.add_page_break()


def contenido(doc):
    doc.add_heading("Resumen ejecutivo", level=1)
    agregar_parrafo(doc, "RESTO SYSTEM es una aplicación web monolítica construida con Laravel 12. El alcance académico solicitado ya cuenta con una base reutilizable: autenticación por sesión, roles y permisos, gestión de áreas y mesas, asignación de camareros, catálogo de productos y creación transaccional de pedidos con detalles. La revisión demuestra existencia de código y pruebas escritas, pero no acredita una ejecución exitosa del sistema ni de la suite sobre el estado actual.")
    agregar_parrafo(doc, "La continuidad del PA1 puede plantearse como estabilización incremental del producto existente. El modelo observable es incremental e iterativo sobre un monolito modular: se conserva el núcleo, se incorporan servicios, políticas, permisos y regresión automatizada, y los cambios se integran en la rama develop. No existe evidencia suficiente para afirmar que se empleó Scrum formal, que se realizaron sprints históricos específicos o que participaron integrantes determinados.")
    agregar_parrafo(doc, "Hallazgos prioritarios: falta validación formal de product_id en el alta por clic; el servidor no vuelve a comprobar allí que el producto esté activo y sea vendible; no hay throttle visible en el login; dos migraciones relevantes presentan problemas de reversibilidad; y existe un cambio local potencialmente defectuoso D::class en la ruta de salida del onboarding.")

    doc.add_heading("Criterio de clasificación", level=2)
    agregar_tabla(doc, ["Estado", "Significado"], [
        ("Implementado", "Existe código ejecutable para el flujo y sus controles principales. No significa que haya sido probado durante esta revisión."),
        ("Parcial", "Existe una implementación sustancial, pero presenta una omisión, inconsistencia o deuda relevante."),
        ("Pendiente de comprobar", "El código o la configuración existe, pero no se ejecutó ni se contrastó con una base desplegada."),
        ("Propuesta", "Mejora sugerida para el Plan de Desarrollo; no representa trabajo histórico ya realizado."),
    ], [3.6, 12.4], 9)

    doc.add_heading("Índice", level=1)
    p = doc.add_paragraph()
    campo(p, 'TOC \\o "1-3" \\h \\z \\u')
    doc.add_page_break()

    doc.add_heading("1. ESTADO ACTUAL Y TECNOLOGÍAS", level=1)
    doc.add_heading("1.1 Identificación de la revisión", level=2)
    agregar_tabla(doc, ["Elemento", "Resultado verificado"], [
        ("Repositorio", r"C:\laragon\www\recreosuite"),
        ("Fecha", "13 de noviembre de 2026 — America/Lima"),
        ("Rama", "develop"),
        ("Commit", "e53e6520ec87fd8a74fc52ff5227543e922341d4"),
        ("Fecha del commit", "20 de octubre de 2026, 23:43:31 -05:00"),
        ("Mensaje", "feat: reorganizar navegacion lateral por modulos"),
        ("Método", "Inspección estática y comandos de solo lectura"),
    ], [4.0, 12.0], 8.8)

    doc.add_heading("1.2 Cambios locales", level=2)
    agregar_codigo(doc, """M  app/Http/Controllers/PosController.php
M  resources/views/pos/order.blade.php
M  resources/views/pos/partials/cart.blade.php
M  routes/web.php
M  tests/Feature/CheckoutPaymentsTest.php
M  tests/Feature/RestaurantEndToEndTest.php
M  tests/Feature/RestaurantOperationalFlowTest.php
?? docs/Informe_tecnico_RecreoSuite_restaurante.docx""")
    agregar_parrafo(doc, "Los cambios del POS ajustan presentación de totales, modal de cobro, mensajes de cocina y redirección al comprobante. También agregan pruebas, pero no se ejecutaron. En routes/web.php:93 aparece localmente [D::class, 'exit'] en lugar de OnboardingController::class; debe corregirse antes de una demostración del ingreso inicial del administrador.")

    doc.add_heading("1.3 Tecnologías y versiones", level=2)
    agregar_tabla(doc, ["Tecnología", "Versión/restricción", "Evidencia"], [
        ("PHP CLI", "8.3.26", "php --version"),
        ("Laravel", "12.70.0", "composer show --locked laravel/framework"),
        ("PHP requerido", "^8.2", "composer.json:9"),
        ("Composer", "2.8.4", "composer --version"),
        ("PHPUnit", "11.5.57", "composer show --locked phpunit/phpunit"),
        ("Laravel Pint", "1.32.1", "composer show --locked laravel/pint"),
        ("Node.js", "24.15.0", "node --version"),
        ("npm", "11.12.1", "npm --version"),
        ("Vite", "7.3.7", "npm list"),
        ("Bootstrap", "5.3.8", "npm list"),
        ("Tailwind CSS", "4.1.18", "npm list"),
        ("Axios", "1.20.0", "npm list"),
        ("Persistencia", "Eloquent + migraciones", "app/Models y database/migrations"),
    ], [4.1, 4.0, 8.0], 8)
    agregar_parrafo(doc, "config/database.php permite SQLite, MySQL, MariaDB, PostgreSQL y SQL Server. SQLite es el valor predeterminado del archivo de configuración, pero no se leyó .env; el motor efectivo de la instalación permanece pendiente de comprobar.")

    doc.add_heading("1.4 Árbol del alcance", level=2)
    agregar_codigo(doc, """app/
├── Http/Controllers/{LoginController,PosController,TableController}.php
├── Http/Middleware/{CheckRole,CheckPermission}.php
├── Http/Requests/{AssignTableWaiter,StoreArea,StoreTable}Request.php
├── Models/{User,Employee,Area,Table,Category,Product,Order,OrderDetail}.php
├── Policies/OrderPolicy.php
└── Services/Restaurant/OrderWorkflowService.php
resources/views/
├── auth/login.blade.php
├── tables/index.blade.php
└── pos/{index,order}.blade.php + partials/cart.blade.php
database/migrations/  (usuarios, sesiones, empleados, áreas, mesas,
                       categorías, productos, pedidos y detalles)
tests/Feature/         (acceso, permisos, comedor, pedidos y flujo operativo)""")

    doc.add_heading("1.5 Arquitectura utilizada", level=2)
    agregar_parrafo(doc, "La solución es un monolito Laravel organizado principalmente con MVC, middleware, Form Requests, políticas y una capa de servicios para el flujo del pedido. Las solicitudes pasan por autenticación, módulo y permiso; los controladores coordinan; OrderWorkflowService contiene transacciones y reglas; los modelos Eloquent persisten; y Blade devuelve vistas o fragmentos HTML.")
    agregar_figura(doc, "architecture.visual-check.2048x1320.light.png", "Figura 1. Arquitectura actual comprobada para el alcance PA2.", 6.8)

    doc.add_heading("2. MÓDULOS Y REQUISITOS", level=1)
    doc.add_heading("2.1 Matriz funcional", level=2)
    filas_funcionales = [
        ("Mostrar login", "Implementado", "Público", "GET /login", "LoginController; auth/login.blade.php", "Prueba manual responsive y accesibilidad"),
        ("Iniciar sesión", "Implementado con deuda", "Cuenta válida", "POST /login", "LoginController.php:20-36", "Throttle y sesión real"),
        ("Cerrar sesión", "Implementado", "Autenticado", "POST /logout", "LoginController.php:38-45", "Invalidación y caché"),
        ("Regenerar sesión", "Implementado", "Cuenta válida", "Interno", "LoginController.php:27-30", "Ejecución no realizada"),
        ("Roles y permisos", "Parcial", "admin/cashier/waiter/kitchen", "Grupos de rutas", "config/access.php; middleware", "Matriz ejecutada"),
        ("Mapa operativo", "Implementado", "admin/cashier/waiter + caja", "GET /pos", "PosController.php:32-48", "Módulo y caja en instalación"),
        ("Administrar mesas", "Implementado", "admin", "/tables", "TableController.php", "Pruebas no ejecutadas"),
        ("Asignar camarero", "Implementado", "admin", "PATCH /tables/table/{table}/waiter", "TableController.php:73-86", "Base migrada y datos"),
        ("Abrir pedido", "Implementado", "roles POS + caja", "GET /pos/table/{table}", "PosController.php:50-68", "Navegador/dispositivo"),
        ("Filtrar menú", "Implementado", "roles POS", "Pantalla pedido", "PosController.php:52-56", "Catálogo desplegado"),
        ("Agregar por clic", "Parcial", "operador autorizado", "POST /pos/order/{table}/add", "PosController; WorkflowService", "Validar product_id y activo/vendible"),
        ("Agregar por código", "Implementado", "operador autorizado", "POST .../barcode", "PosController.php:81-99", "Códigos válidos e inválidos"),
        ("Crear pedido/detalle", "Implementado", "operador autorizado", "Al primer producto", "WorkflowService.php:19-47", "Ejecución transaccional"),
        ("Cambiar cantidad", "Implementado", "operador del pedido", "POST /pos/detail/{detail}/update", "PosController.php:108-126", "Pruebas no ejecutadas"),
        ("Añadir nota", "Implementado", "operador; detalle pending", "POST .../note", "PosController.php:129-139", "Pruebas no ejecutadas"),
        ("Retirar producto", "Implementado", "operador; detalle pending", "DELETE /pos/detail/{detail}", "PosController; WorkflowService", "Pruebas no ejecutadas"),
        ("Calcular total", "Implementado", "Interno", "Servicio", "WorkflowService.php:126-130", "Bordes y redondeo"),
        ("Fecha y hora", "Implementado por timestamps", "Interno", "Persistencia", "Migraciones orders/details", "Zona de base real"),
        ("Dividir cuenta", "Parcial/no implementado", "Operador POS", "POST .../split", "PosController.php:179-184", "El método solo redirige"),
    ]
    agregar_tabla(doc, ["Función", "Estado", "Rol", "Ruta/pantalla", "Evidencia", "Pendiente"], filas_funcionales, [2.6, 2.3, 2.5, 3.4, 3.5, 3.4], 6.7)

    doc.add_heading("2.2 Autenticación, sesiones y acceso", level=2)
    agregar_lista(doc, [
        "GET /login muestra el formulario si no existe sesión autenticada.",
        "POST /login valida correo obligatorio con formato email y contraseña obligatoria (LoginController.php:22-25).",
        "Auth::attempt() utiliza el guard web de sesión y el proveedor Eloquent configurado en config/auth.php:38-66.",
        "Al autenticar se regenera el identificador de sesión (LoginController.php:27-30).",
        "Las credenciales inválidas regresan al formulario conservando únicamente el correo.",
        "Logout invalida la sesión, regenera CSRF y devuelve Clear-Site-Data (LoginController.php:38-45).",
        "El formulario incorpora @csrf en resources/views/auth/login.blade.php:422-423.",
        "User oculta password y remember_token; password usa cast hashed (User.php:32-45).",
        "No se encontró throttle asociado al POST /login en routes/web.php:48-50.",
    ])
    agregar_tabla(doc, ["Rol", "Acceso relevante"], [
        ("admin", "Todas las capacidades por comodín *; administración de mesas y cualquier pedido."),
        ("cashier", "pos.manage, pos.discount y pos.checkout; puede operar cualquier pedido."),
        ("waiter", "pos.manage; solo opera el pedido cuyo camarero coincide con su ficha activa."),
        ("kitchen", "No ingresa al POS; dispone de permisos de cocina."),
    ], [3.3, 12.7], 8.7)

    doc.add_heading("2.3 Asignación de mesas y camareros", level=2)
    agregar_lista(doc, [
        "Las rutas de administración están dentro del grupo role:admin (routes/web.php:188-301).",
        "AssignTableWaiterRequest exige dining-room.manage; admin lo obtiene por el comodín *.",
        "La asignación puede ser nula para dejar la mesa sin camarero.",
        "El empleado elegido debe estar activo, tener position=waiter y una cuenta con role=waiter.",
        "Se actualiza tables.assigned_employee_id y se registra auditoría del valor anterior y nuevo.",
        "Al abrir un pedido, un camarero necesita ficha activa; no puede tomar la mesa de otro camarero; una mesa sin asignación se le autoasigna.",
        "Admin y cajero conservan al camarero asignado a la mesa si existe.",
    ])

    doc.add_heading("2.4 Importes, estado y fecha/hora", level=2)
    agregar_codigo(doc, """subtotal = Σ(order_details.price × order_details.quantity)
total = máximo(0, subtotal - discount + tip)""")
    agregar_parrafo(doc, "El detalle conserva una copia de product.price en el momento del alta. El precio promocional no se usa en este flujo. Si ya existe una partida pending del mismo producto, se incrementa quantity. El alta no valida stock; el control de stock ocurre al cobrar, fuera del núcleo pedido.")
    agregar_parrafo(doc, "No existe order_date. orders y order_details incluyen created_at y updated_at por $table->timestamps(). La zona predeterminada es America/Lima (config/app.php:68) y SetTimezone puede sustituirla con el valor almacenado en settings. La coincidencia con la base real queda pendiente.")

    doc.add_heading("3. INSUMOS PARA EL DIAGRAMA DE CLASES", level=1)
    doc.add_heading("3.1 Clases esenciales", level=2)
    clases = [
        ("LoginController", "Controlador", "Login, logout y redirección por rol", "show, login, logout, redirectUser"),
        ("PosController", "Controlador", "Coordina mapa, menú y cambios del pedido", "index, order, addToOrder, updateQuantity, updateNote, removeItem"),
        ("TableController", "Controlador", "Áreas, mesas, posiciones y camareros", "index, storeTable, assignWaiter, toggle, updatePositions"),
        ("OrderWorkflowService", "Servicio", "Reglas transaccionales del pedido", "addProduct, removeDetail, move, advanceKitchen, recalculate"),
        ("OrderPolicy", "Política", "Decide quién ve/actualiza el pedido", "view, update, operate"),
        ("CheckPermission", "Middleware", "Evalúa capacidades por rol", "handle"),
        ("StoreTableRequest", "Validación", "Autoriza y valida mesa", "authorize, rules"),
        ("AssignTableWaiterRequest", "Validación", "Autoriza y valida asignación", "authorize, rules"),
        ("User", "Modelo", "Cuenta autenticable y permisos", "hasPermission, employee"),
        ("Employee", "Modelo", "Ficha laboral", "user, assignedTables"),
        ("Area", "Modelo", "Agrupa mesas", "tables"),
        ("Table", "Modelo", "Mesa, estado y camarero", "area, orders, assignedWaiter"),
        ("Category", "Modelo", "Agrupa productos", "products"),
        ("Product", "Modelo", "Producto y precio del menú", "category, ingredients, orderDetails"),
        ("Order", "Agregado", "Cabecera del pedido", "table, user, waiter, details"),
        ("OrderDetail", "Modelo", "Partida del pedido", "order, product"),
    ]
    agregar_tabla(doc, ["Clase", "Tipo", "Responsabilidad", "Métodos"], clases, [3.4, 2.2, 6.5, 6.2], 7.4)
    agregar_figura(doc, "class-diagram.png", "Figura 2. Clases, responsabilidades y relaciones reales del alcance.", 6.8)
    agregar_parrafo(doc, "User–Employee es 1 a 0..1 porque employees.user_id es nullable y unique. Una mesa puede quedar sin camarero. Un pedido conserva waiter_employee_id independientemente de que la asignación futura de la mesa cambie.")

    doc.add_heading("4. INSUMOS PARA EL DIAGRAMA DE BASE DE DATOS", level=1)
    agregar_parrafo(doc, "La siguiente estructura procede de migraciones y modelos. No se inspeccionó el esquema de una base desplegada; por tanto, su correspondencia física está pendiente de comprobar.")
    tablas_db = [
        ("users", "id, name, email, password, role, timestamps", "PK id; email UNIQUE; role ENUM", "email_verified_at y remember_token NULL"),
        ("sessions", "id, user_id, ip_address, user_agent, payload, last_activity", "PK id; índices user_id/last_activity; sin FK declarada", "user_id, IP y agente NULL"),
        ("employees", "id, user_id, full_name, position, is_active, timestamps", "FK user_id→users; user_id UNIQUE; nullOnDelete", "user_id y datos complementarios NULL"),
        ("areas", "id, name, timestamps", "PK id", "name NOT NULL"),
        ("tables", "id, area_id, assigned_employee_id, name, seats, status, is_active, x/y, timestamps", "FK área cascade; FK empleado nullOnDelete; status ENUM", "assigned_employee_id NULL"),
        ("categories", "id, name, image, is_active, timestamps", "PK id", "image NULL"),
        ("products", "id, category_id, name, barcode, price, stock, activo/vendible, timestamps", "FK categoría cascade; barcode UNIQUE", "barcode, costo, stock, imagen y promoción NULL"),
        ("orders", "id, table_id, user_id, waiter_employee_id, status, total, discount, tip, timestamps", "FK mesa/usuario/empleado; status ENUM", "table_id y waiter_employee_id NULL en esquema final"),
        ("order_details", "id, order_id, product_id, quantity, price, note, status, timestamps", "FK pedido cascade; FK producto; status ENUM", "note NULL"),
        ("cash_registers", "id, user_id, apertura/cierre, importes, status, timestamps", "FK user_id cascade; status ENUM", "cierre e importes finales NULL"),
    ]
    agregar_tabla(doc, ["Tabla", "Campos principales", "Claves/restricciones", "Nulabilidad"], tablas_db, [2.5, 6.2, 5.6, 4.3], 6.9)
    agregar_figura(doc, "er-diagram.png", "Figura 3. Entidades, claves y cardinalidades del alcance.", 6.8)
    agregar_lista(doc, [
        "orders.table_id nació obligatorio y una migración posterior lo hizo nullable.",
        "No existe una restricción UNIQUE que garantice un único pedido pending por mesa; la aplicación usa transacción y bloqueo de la mesa.",
        "order_details.price conserva el precio aplicado al pedido.",
        "Estados: mesa available/occupied/reserved; pedido pending/completed/cancelled; detalle pending/cooking/served.",
        "La migración add_role_to_users solo crea role si falta, pero down() la elimina siempre que exista: riesgo de rollback.",
        "La migración add_barcode_to_products agrega barcode y no lo elimina en down(): reversibilidad incompleta.",
    ])

    doc.add_heading("5. INSUMOS PARA EL DIAGRAMA DE SECUENCIA", level=1)
    agregar_figura(doc, "order-sequence-full.png", "Figura 4. Secuencia real para agregar un producto al pedido.", 6.8)
    doc.add_heading("5.1 Secuencia principal", level=2)
    agregar_lista(doc, [
        "La pantalla envía product_id y CSRF mediante fetch() (pos/order.blade.php:348-353).",
        "La solicitud llega a POST /pos/order/{table}/add (routes/web.php:120).",
        "Hereda auth, module:restaurant y permission:pos.manage.",
        "PosController protege con OrderPolicy un pedido pending existente.",
        "Product::findOrFail() obtiene el producto; un identificador inexistente da 404.",
        "OrderWorkflowService::addProduct() inicia DB::transaction.",
        "Se bloquea la fila de mesa y se valida is_active.",
        "Se busca y bloquea el pedido pending de esa mesa.",
        "Si no existe, resolveWaiter() decide y conserva el camarero; luego se crea orders.",
        "Se crea order_details o se incrementa la partida pending existente.",
        "La mesa cambia a occupied y se recalcula orders.total.",
        "Se confirma la transacción; el controlador renderiza cart.blade.php y responde HTML 200.",
    ], numerada=True)
    doc.add_heading("5.2 Alternativas", level=2)
    agregar_tabla(doc, ["Alternativa", "Comportamiento real", "Evidencia"], [
        ("Sin permiso general", "CheckPermission aborta con HTTP 403", "CheckPermission.php:14-24"),
        ("Camarero ajeno", "OrderPolicy devuelve falso; Laravel responde 403", "OrderPolicy.php:20-36"),
        ("Mesa inactiva", "ValidationException; normalmente 422 en AJAX", "WorkflowService.php:20-23"),
        ("Ficha de camarero inválida", "ValidationException y rollback", "WorkflowService.php:138-141"),
        ("Mesa de otro camarero", "ValidationException y rollback", "WorkflowService.php:142-144"),
        ("Producto inexistente", "findOrFail produce 404", "PosController.php:74"),
        ("product_id mal formado", "No existe validación explícita en alta por clic", "PosController.php:71-78"),
        ("Producto inactivo/no vendible", "La UI lo filtra, pero addToOrder no lo revalida", "PosController.php:52-56,71-78"),
    ], [4.2, 7.2, 5.0], 8)

    doc.add_heading("6. INSUMOS PARA EL DIAGRAMA DE DESPLIEGUE", level=1)
    agregar_figura(doc, "deployment.visual-check.2048x1320.light.png", "Figura 5. Despliegue comprobable sin inventar infraestructura.", 6.8)
    doc.add_heading("6.1 Comprobado", level=2)
    agregar_lista(doc, [
        "El repositorio se encuentra bajo C:\\laragon\\www, evidencia de entorno local Laragon.",
        "La aplicación sirve Blade y recursos compilados por Vite.",
        "Existen rutas de manifest y estado PWA en routes/web.php:51-53,71.",
        "La configuración permite varias conexiones SQL.",
        "El driver de sesión predeterminado del archivo es database y su tabla sessions.",
        "Las pruebas usan SQLite :memory:, sesión array, correo array y cola sync.",
    ])
    doc.add_heading("6.2 Documentado pero no verificado", level=2)
    agregar_parrafo(doc, "docs/ARCHITECTURE.md describe navegador, PWA, futuro contenedor Capacitor y modalidades nube/local. docs/PLAN.md menciona un dominio y una base locales. Como no se inspeccionó .env ni el servidor, esos datos no son evidencia del runtime actual.")
    doc.add_heading("6.3 No comprobado", level=2)
    agregar_lista(doc, ["Servidor web efectivo.", "Puerto, dominio y protocolo activos.", "Motor y versión de la base desplegada.", "Estado de una instalación productiva.", "Certificado TLS.", "Contenedor Capacitor/Android operativo.", "Infraestructura cloud."])

    doc.add_heading("7. FRAGMENTOS DE CÓDIGO", level=1)
    doc.add_heading("7.1 Autenticación", level=2)
    agregar_parrafo(doc, "Archivo: app/Http/Controllers/LoginController.php; método login(); líneas 20-35.")
    agregar_codigo(doc, """$credentials = $request->validate([
    'email' => ['required', 'email'],
    'password' => ['required'],
]);

if (Auth::attempt($credentials)) {
    $request->session()->regenerate();
    return $this->redirectUser();
}

return back()->withErrors([
    'email' => 'Las credenciales no coinciden con nuestros registros.',
])->onlyInput('email');""")
    agregar_parrafo(doc, "Valida la forma básica, delega la autenticación a Laravel y regenera la sesión. No revela cuál credencial falló. La ruta carece de throttle visible.")

    doc.add_heading("7.2 Validación y autorización del pedido", level=2)
    agregar_parrafo(doc, "Archivo: app/Services/Restaurant/OrderWorkflowService.php; método addProduct(); líneas 19-29.")
    agregar_codigo(doc, """return DB::transaction(function () use ($table, $product, $actor): Order {
    $lockedTable = Table::whereKey($table->id)
        ->lockForUpdate()
        ->firstOrFail();

    if (! $lockedTable->is_active) {
        throw ValidationException::withMessages([
            'table' => 'La mesa está fuera de servicio.',
        ]);
    }

    $order = Order::where('table_id', $lockedTable->id)
        ->where('status', 'pending')
        ->lockForUpdate()
        ->first();

    if ($order) {
        abort_unless($actor->can('update', $order), 403);
    }""")
    agregar_parrafo(doc, "Comprueba la mesa, bloquea filas críticas y consulta la política. No reemplaza la validación faltante de product_id en el controlador.")

    doc.add_heading("7.3 Guardado del pedido y detalles", level=2)
    agregar_parrafo(doc, "Archivo: app/Services/Restaurant/OrderWorkflowService.php; método addProduct(); líneas 29-46.")
    agregar_codigo(doc, """$waiter = $this->resolveWaiter($lockedTable, $actor);
$order = Order::create([
    'table_id' => $lockedTable->id,
    'user_id' => $actor->id,
    'waiter_employee_id' => $waiter?->id,
    'status' => 'pending',
    'total' => 0,
]);

$detail = $order->details()
    ->where('product_id', $product->id)
    ->where('status', 'pending')
    ->first();

$detail
    ? $detail->increment('quantity')
    : $order->details()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => $product->price,
        'status' => 'pending',
    ]);

$lockedTable->update(['status' => 'occupied']);
$this->recalculate($order);""")
    agregar_parrafo(doc, "Crea la cabecera, guarda el camarero responsable, crea o incrementa la partida, ocupa la mesa y recalcula el total dentro de la transacción.")

    doc.add_heading("8. CALIDAD Y EVIDENCIA DISPONIBLE", level=1)
    doc.add_heading("8.1 Estándares y convenciones", level=2)
    agregar_tabla(doc, ["Aspecto", "Evidencia", "Evaluación"], [
        ("PSR-4", "composer.json:27-36", "Implementado"),
        ("Formato", "Laravel Pint en dependencias", "Disponible; no ejecutado"),
        ("MVC", "Controladores, modelos y vistas", "Implementado"),
        ("Servicios", "OrderWorkflowService", "Parcial positivo"),
        ("Form Requests", "Mesa, área y asignación", "Implementado"),
        ("Policies", "OrderPolicy", "Implementado"),
        ("Middleware", "CheckPermission y CheckRole", "Implementado"),
        ("Validación", "Login, cantidad, nota, asignación", "Parcial"),
        ("Transacciones", "Alta, retiro y traslado", "Implementado"),
        ("Concurrencia", "lockForUpdate", "Implementado en flujo principal"),
        ("Auditoría", "Asignación, movimiento, cancelación", "Parcial"),
        ("CSRF", "Formularios y fetch", "Implementado"),
        ("Mobile first", "Documentación y vistas adaptativas", "Validación física pendiente"),
    ], [4.2, 7.0, 5.0], 8.2)

    doc.add_heading("8.2 Manejo de errores y brechas", level=2)
    agregar_parrafo(doc, "Se observan 403 por rol/política, 422 por ValidationException, 404 por findOrFail, transacciones con rollback, bloqueo de partidas no pendientes y bloqueo de mesa/pedido. Brechas: login sin throttle visible; product_id sin validación formal; ausencia de comprobación activo/vendible en alta por clic; ausencia de UNIQUE pending por mesa; excepciones silenciadas en SetTimezone; textos dañados por codificación; ruta local D::class; y migraciones no completamente reversibles.")

    doc.add_heading("8.3 Pruebas escritas", level=2)
    agregar_tabla(doc, ["Archivo", "Casos"], [
        ("AccessControlBaselineTest.php", "4"),
        ("PermissionMiddlewareTest.php", "4"),
        ("DiningRoomManagementTest.php", "5"),
        ("OrderWorkflowTest.php", "5"),
        ("RestaurantOperationalFlowTest.php", "6"),
        ("RestaurantEndToEndTest.php", "1"),
        ("CheckoutPaymentsTest.php", "8"),
        ("TOTAL", "33"),
    ], [12.0, 3.0], 9)
    agregar_parrafo(doc, "Método: conteo estático de métodos public function test_... únicamente en los siete archivos del alcance. No equivale a cobertura ni a resultados aprobados.")
    agregar_lista(doc, [
        "Las pruebas escritas cubren redirección no autenticada, denegación por rol, asignación válida e inválida de camareros, preservación de historial, posiciones, creación de pedido, traslado, cancelación, estados de cocina, rechazo de pedido ajeno y prevención de doble cobro.",
        "No se ejecutó la suite durante esta revisión.",
        "No se encontró artefacto actual JUnit, PHPUnit o cobertura asociado al commit y cambios locales.",
        "docs/TESTING.md contiene resultados del 13/10/2026 que ya son obsoletos porque afirmaban que solo existían dos pruebas.",
        "phpunit.xml configura SQLite :memory:, sesión array, correo array y cola sync.",
        "Cobertura, rendimiento, accesibilidad y densidad de defectos no fueron medidos.",
    ])

    doc.add_heading("9. INFORMACIÓN PARA EL PLAN DE DESARROLLO", level=1)
    doc.add_heading("9.1 Continuidad del PA1", level=2)
    agregar_parrafo(doc, "RESTO SYSTEM continúa sobre una base existente: reutiliza autenticación, cuentas y roles; conserva áreas y mesas; separa cuenta y empleado; asigna camareros; usa categorías y productos como carta; mantiene Order y OrderDetail; introduce un servicio transaccional, una política y pruebas feature. docs/PLAN.md organiza la operación de restaurante como una fase, pero no se inspeccionó un entregable académico PA1; la correspondencia exacta debe completarse con el documento del curso.")

    doc.add_heading("9.2 Modelo de construcción", level=2)
    agregar_parrafo(doc, "Modelo defendible: construcción incremental e iterativa sobre un monolito modular Laravel. Git registra cambios sucesivos en develop; el plan se divide en fases; el núcleo se conserva y amplía; y las pruebas feature funcionan como regresión. No hay evidencia suficiente para afirmar Scrum formal, sprints históricos concretos, integrantes, fechas o esfuerzos.")

    doc.add_heading("9.3 Organización ágil", level=2)
    agregar_parrafo(doc, "Comprobado: rama develop, commits incrementales, plan por fases, puertas de calidad, backlog de pruebas, decisiones en docs/decisions y pruebas funcionales. Propuesta académica:")
    agregar_tabla(doc, ["Incremento propuesto", "Contenido", "Salida esperada"], [
        ("1. Acceso seguro", "Login válido/inválido, logout, throttle, matriz de roles", "Pruebas de regeneración e invalidación"),
        ("2. Mesas y camareros", "CRUD mínimo, asignación y rechazo de fichas inválidas", "Estados y auditoría demostrables"),
        ("3. Registro de pedidos", "Producto, transacción, cantidades, notas, totales y autorización", "Flujo consistente y concurrente"),
        ("4. Calidad y demostración", "Suite aislada, recorrido manual, dispositivos y evidencias", "Informe y demostración reproducible"),
    ], [4.3, 8.2, 5.0], 8.2)
    agregar_parrafo(doc, "Los incrementos son una propuesta para el PA2; no deben presentarse como sprints ya realizados.")

    doc.add_heading("9.4 Herramientas y prácticas", level=2)
    agregar_tabla(doc, ["Finalidad", "Herramienta o práctica"], [
        ("Versionado", "Git y rama develop"),
        ("Backend", "PHP 8.3 y Laravel 12"),
        ("Dependencias", "Composer y npm"),
        ("Persistencia", "Eloquent y migraciones"),
        ("Frontend", "Blade, Bootstrap, Sass y JavaScript"),
        ("Empaquetado", "Vite"),
        ("Pruebas", "PHPUnit, RefreshDatabase y SQLite en memoria"),
        ("Formato", "Laravel Pint disponible"),
        ("Seguridad", "Middleware, policies, CSRF, hashing y regeneración de sesión"),
        ("Integridad", "Transacciones y lockForUpdate"),
        ("Documentación", "Markdown y decisiones estructurales"),
        ("Configuración", "Variables de entorno sin credenciales versionadas"),
    ], [5.0, 11.0], 8.7)

    doc.add_heading("9.5 Demostrable, pendiente y ajustes mínimos", level=2)
    agregar_tabla(doc, ["Categoría", "Contenido"], [
        ("Demostrable por código", "Login/sesión; logout; roles; política por camarero; áreas/mesas; asignación; menú filtrado; pedido/detalle transaccional; precio congelado; mesa ocupada; total; timestamps; pruebas escritas."),
        ("No afirmar aún", "Suite aprobada; igualdad con base desplegada; producción; dispositivos reales; WCAG AA completo; concurrencia en todos los motores; sprints o métricas históricas."),
    ], [4.2, 12.0], 8.6)
    agregar_tabla(doc, ["Prioridad", "Ajuste", "Clasificación"], [
        ("Alta", "Corregir D::class en ruta local del onboarding", "Defecto actual"),
        ("Alta", "Validar product_id: required, integer, exists", "Propuesta"),
        ("Alta", "Revalidar is_active e is_saleable dentro del servicio", "Propuesta"),
        ("Alta", "Añadir throttle al login", "Propuesta"),
        ("Alta", "Ejecutar 33 casos en ambiente aislado y conservar resultado", "Pendiente"),
        ("Media", "Pruebas específicas de login y logout", "Propuesta"),
        ("Media", "Pruebas de producto inactivo/no vendible/payload inválido", "Propuesta"),
        ("Media", "Verificar migraciones y rollback en base desechable", "Pendiente"),
        ("Media", "Corregir reversibilidad de rol y barcode", "Propuesta"),
        ("Media", "Recorrido manual móvil/tableta/escritorio", "Propuesta"),
        ("Baja", "Corregir codificación de textos", "Propuesta"),
    ], [2.2, 10.4, 3.4], 8.2)

    doc.add_heading("Conclusiones", level=1)
    agregar_parrafo(doc, "RESTO SYSTEM posee una base funcional suficiente para construir el PA2 sin reconstrucción. La evidencia más sólida es la convergencia de rutas protegidas, modelos Eloquent, política de pedido, servicio transaccional, migraciones y casos feature. La estrategia recomendada es estabilizar el alcance: completar validaciones, reforzar el login, ejecutar la suite aislada, corregir migraciones y producir evidencia de recorrido manual.")
    agregar_parrafo(doc, "La entrega académica debe mantener una separación explícita entre código implementado, pruebas escritas, resultados ejecutados y propuestas futuras. Esta distinción evita presentar como probado lo que únicamente está programado y permite formular un Plan de Desarrollo verificable.")

    doc.add_heading("ANEXO A. RUTAS DEL ALCANCE", level=1)
    rutas = [
        ("GET", "/login", "LoginController@show", "Pública"),
        ("POST", "/login", "LoginController@login", "Pública + CSRF"),
        ("POST", "/logout", "LoginController@logout", "Sesión en uso normal"),
        ("GET", "/pos", "PosController@index", "auth + restaurant + pos.manage + caja"),
        ("GET", "/pos/table/{table}", "PosController@order", "auth + restaurant + pos.manage + caja"),
        ("POST", "/pos/order/{table}/add", "PosController@addToOrder", "auth + restaurant + pos.manage"),
        ("POST", "/pos/order/{table}/barcode", "PosController@addByBarcode", "auth + restaurant + pos.manage"),
        ("POST", "/pos/detail/{detail}/update", "PosController@updateQuantity", "auth + restaurant + pos.manage + policy"),
        ("POST", "/pos/detail/{detail}/note", "PosController@updateNote", "auth + restaurant + pos.manage + policy"),
        ("DELETE", "/pos/detail/{detail}", "PosController@removeItem", "auth + restaurant + pos.manage + policy"),
        ("GET", "/tables", "TableController@index", "auth + role:admin + restaurant"),
        ("POST", "/tables/table", "TableController@storeTable", "admin + FormRequest"),
        ("PATCH", "/tables/table/{table}/waiter", "TableController@assignWaiter", "admin + FormRequest"),
    ]
    agregar_tabla(doc, ["Método", "URI", "Acción", "Protección"], rutas, [2.0, 5.7, 5.2, 5.4], 7.5)

    doc.add_heading("ANEXO B. FUENTES PRINCIPALES", level=1)
    fuentes = [
        ("Autenticación", "app/Http/Controllers/LoginController.php:20-45"),
        ("Rutas", "routes/web.php:48-50,70,110-139,188-301"),
        ("Permisos", "config/access.php:4-37; app/Models/User.php:57-62"),
        ("Política", "app/Policies/OrderPolicy.php:10-37"),
        ("POS", "app/Http/Controllers/PosController.php:32-149,405-428"),
        ("Pedido", "app/Services/Restaurant/OrderWorkflowService.php:17-155"),
        ("Mesas", "app/Http/Controllers/TableController.php:20-137"),
        ("Validaciones", "app/Http/Requests/AssignTableWaiterRequest.php; StoreTableRequest.php"),
        ("Modelos", "app/Models/{User,Employee,Area,Table,Category,Product,Order,OrderDetail}.php"),
        ("Migraciones", "database/migrations/*users*, *sessions*, *employees*, *tables*, *products*, *orders*, *order_details*"),
        ("Pruebas", "tests/Feature/AccessControlBaselineTest.php; PermissionMiddlewareTest.php; DiningRoomManagementTest.php; OrderWorkflowTest.php; RestaurantOperationalFlowTest.php; RestaurantEndToEndTest.php; CheckoutPaymentsTest.php"),
        ("Arquitectura", "docs/ARCHITECTURE.md; docs/PLAN.md; docs/TESTING.md; docs/MODULES.md"),
    ]
    agregar_tabla(doc, ["Tema", "Ruta y líneas"], fuentes, [4.0, 12.5], 8.2)

    doc.add_heading("ANEXO C. NOTA METODOLÓGICA", level=1)
    agregar_parrafo(doc, "La inspección empleó git status/log/diff, búsqueda de archivos y texto, lectura de código con numeración de líneas y consulta local de versiones instaladas. No se leyó .env, no se consultó una base desplegada, no se ejecutó artisan test, no se migró, no se sembró información y no se realizó tráfico externo. Las figuras de arquitectura y secuencia fueron validadas estructuralmente; las de clases y entidad–relación se elaboraron a partir de relaciones reales encontradas en modelos y migraciones.")


def construir():
    doc = Document()
    configurar_documento(doc)
    portada(doc)
    contenido(doc)
    props = doc.core_properties
    props.title = "RESTO SYSTEM — Informe técnico PA2"
    props.subject = "Plan de Desarrollo de Software — Construcción de Software"
    props.author = "Informe generado a partir de evidencia del repositorio"
    props.keywords = "RESTO SYSTEM, PA2, Laravel, pedidos, mesas, autenticación"
    doc.save(OUTPUT)
    print(OUTPUT)


if __name__ == "__main__":
    construir()
