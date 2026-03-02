<div class="mb-4">
    <h3 class="fw-bold"><i class="bi bi-bar-chart-fill me-2"></i>Módulo de Reportes</h3>
    <p class="text-muted">Seleccione el tipo de reporte que desea generar o consultar.</p>
</div>

<div class="row">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-person-badge text-primary fs-3"></i>
                    </div>
                </div>
                <h5 class="card-title fw-bold">Rendimientos Mensuales</h5>
                <p class="card-text text-muted">Gestión de liquidaciones mensuales, historial de pagos y reportes PDF
                    para socios.</p>
                <div class="d-grid gap-2">
                    <a href="dashboard.php?modulo=reportes&action=nuevo_pago_socio" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Liquidar Rendimientos
                    </a>
                    <a href="dashboard.php?modulo=reportes&action=pagos_socios" class="btn btn-outline-primary">
                        <i class="bi bi-list-ul me-1"></i> Rendimientos pagados
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-wallet2 text-info fs-3"></i>
                    </div>
                </div>
                <h5 class="card-title fw-bold">Portafolios de Inversión</h5>
                <p class="card-text text-muted">Consulta el resumen de aportes activos y capital total invertido por
                    cada socio.</p>
                <div class="d-grid gap-2">
                    <a href="dashboard.php?modulo=reportes&action=portafolios_inversiones"
                        class="btn btn-info text-white">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Ver Portafolios
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-file-earmark-bar-graph text-success fs-3"></i>
                    </div>
                </div>
                <h5 class="card-title fw-bold">Reporte General</h5>
                <p class="card-text text-muted">Listado detallado de préstamos generados en un rango de fechas
                    específico.</p>
                <div class="d-grid gap-2">
                    <a href="dashboard.php?modulo=reportes&action=reporte_general_prestamos"
                        class="btn btn-success text-white">
                        <i class="bi bi-calendar-range me-1"></i> Filtrar por Fechas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-cash-stack text-warning fs-3"></i>
                    </div>
                </div>
                <h5 class="card-title fw-bold">Reporte de Pagos</h5>
                <p class="card-text text-muted">Listado consolidado de pagos y recaudos filtrado por rango de fechas.
                </p>
                <div class="d-grid gap-2">
                    <a href="dashboard.php?modulo=reportes&action=reporte_general_pagos"
                        class="btn btn-warning text-white">
                        <i class="bi bi-calendar-range me-1"></i> Filtrar Pagos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Otros reportes pueden ir aquí en el futuro -->
</div>