<?php
session_start();
if (isset($_POST['eliminar_partida'])) {

    $partidaEliminar = $_POST['eliminar_partida'];

    $_SESSION['libro'] = array_filter($_SESSION['libro'], function($fila) use ($partidaEliminar) {
        return $fila['partida'] != $partidaEliminar;
    });

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if (!isset($_SESSION['libro'])) {
    
    $_SESSION['libro'] = [];
}

/* ===========================
   REGISTRO CONTABLE SIN BD
=========================== */

if (isset($_POST['descripcion'])) {

    $descripcion = $_POST['descripcion'];

    $partida = count($_SESSION['libro']) > 0 
        ? end($_SESSION['libro'])['partida'] + 1 
        : 1;

    preg_match('/\$(\d+(\.\d+)?)/', $descripcion, $montoMatch);
    $monto = $montoMatch[1] ?? 0;

    if ($monto > 0) {

        $iva = round($monto * 0.13 / 1.13, 2);
        $base = round($monto - $iva, 2);

       function guardar($partida,$descripcion,$codigo,$cuenta,$debe,$haber) {
    $_SESSION['libro'][] = [
        "partida"=>$partida,
        "descripcion"=>$descripcion,
        "codigo"=>$codigo,
        "cuenta"=>$cuenta,
        "debe"=>$debe,
        "haber"=>$haber,
        'operacion' => $_POST['descripcion'] ?? '',
    ];
}

        if (stripos($descripcion, "capital") !== false) {

            guardar($partida,$descripcion,'1101','Efectivo y Equivalentes',$monto,0);
            guardar($partida,$descripcion,'3101','Capital Social',0,$monto);

        } elseif (stripos($descripcion, "compre") !== false 
            && stripos($descripcion, "credito") === false) {

            guardar($partida,$descripcion,'1105','Inventarios',$base,0);
            guardar($partida,$descripcion,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcion,'1101','Efectivo y Equivalentes',0,$monto);

        } elseif (stripos($descripcion, "compre") !== false 
            && stripos($descripcion, "credito") !== false) {

            guardar($partida,$descripcion,'1105','Inventarios',$base,0);
            guardar($partida,$descripcion,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcion,'2101','Cuentas por Pagar Proveedores',0,$monto);

        } elseif (stripos($descripcion, "vendi") !== false 
            && stripos($descripcion, "credito") === false) {

            guardar($partida,$descripcion,'1101','Efectivo y Equivalentes',$monto,0);
            guardar($partida,$descripcion,'4101','Ventas',0,$base);
            guardar($partida,$descripcion,'2102','IVA Débito Fiscal',0,$iva);

        } elseif (stripos($descripcion, "vendi") !== false 
            && stripos($descripcion, "credito") !== false) {

            guardar($partida,$descripcion,'1103','Cuentas por Cobrar Clientes',$monto,0);
            guardar($partida,$descripcion,'4101','Ventas',0,$base);
            guardar($partida,$descripcion,'2102','IVA Débito Fiscal',0,$iva);

        } elseif (stripos($descripcion, "devolucion") !== false) {

            guardar($partida,$descripcion,'4102','Rebajas y Devoluciones s/ Ventas',$base,0);
            guardar($partida,$descripcion,'2102','IVA Débito Fiscal',$iva,0);
            guardar($partida,$descripcion,'1101','Efectivo y Equivalentes',0,$monto);

        } elseif (stripos($descripcion, "pague") !== false) {

            guardar($partida,$descripcion,'5102','Gastos de Venta',$monto,0);
            guardar($partida,$descripcion,'1101','Efectivo y Equivalentes',0,$monto);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        background: #0f2027;
        color: #ffffff;
        min-height: 100vh;
    }

    .card {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 15px;
        color: white;
    }

    .card-header {
        font-weight: bold;
        letter-spacing: 1px;
        border-radius: 15px 15px 0 0 !important;
    }

    .form-control {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
    }

    .form-control::placeholder {
        color: #ccc;
    }

    .form-control:focus {
        background: rgba(255,255,255,0.12);
        color: white;
        border-color: #00ffae;
        box-shadow: 0 0 10px #00ffae;
    }

    .btn-success {
        background: linear-gradient(45deg, #00b09b, #96c93d);
        border: none;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-success:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px #00ffae;
    }

    .alert-success {
        background: linear-gradient(45deg, #00b09b, #96c93d);
        border: none;
        color: black;
    }

    .table {
        color: white;
    }

    .table thead {
        background-color: rgba(255,255,255,0.1);
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255,255,255,0.03);
    }

    .table-bordered td, 
    .table-bordered th {
        border: 1px solid rgba(255,255,255,0.15);
    }

    hr {
        border-color: rgba(255,255,255,0.2);
    }

    h6 {
        color: #00ffae !important;
    }
</style>

<script>
function calcular() {
    let salario = parseFloat(document.getElementById("salario").value);

    if (isNaN(salario) || salario <= 0) {
        alert("Ingrese un salario válido");
        return;
    }

    let isss = salario * 0.03;
    let afp = salario * 0.0725;
    let rentaNeta = salario - isss - afp;

    let isr = 0;

    if (rentaNeta <= 472) {
        isr = 0;
    } else if (rentaNeta <= 895.24) {
        isr = (rentaNeta - 472) * 0.10 + 17.67;
    } else if (rentaNeta <= 2038.10) {
        isr = (rentaNeta - 895.24) * 0.20 + 60;
    } else {
        isr = (rentaNeta - 2038.10) * 0.30 + 288.57;
    }

    let liquido = rentaNeta - isr;

    document.getElementById("salarioBruto").innerText = "$" + salario.toFixed(2);
    document.getElementById("isss").innerText = "$" + isss.toFixed(2);
    document.getElementById("afp").innerText = "$" + afp.toFixed(2);
    document.getElementById("renta").innerText = "$" + rentaNeta.toFixed(2);
    document.getElementById("isr").innerText = "$" + isr.toFixed(2);
    document.getElementById("liquido").innerText = "$" + liquido.toFixed(2);
}
</script>

<body>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-8 col-md-10">

<!-- ===================== -->
<!-- CALCULADORA (INTOCABLE) -->
<!-- ===================== -->

<div class="card shadow-lg border-0">

    <div class="card-header text-center bg-transparent">
        <h4 class="mb-0">CALCULADORA SALARIAL SV</h4>
        <small>Período: Mensual</small>
    </div>

    <div class="card-body">

        <div class="mb-3">
            <label class="form-label fw-bold">Salario Mensual ($)</label>
            <input type="number" id="salario" class="form-control" placeholder="Ej: 550">
        </div>

        <div class="d-grid mb-4">
            <button class="btn btn-success" onclick="calcular()">Calcular</button>
        </div>

        <h6 class="text-uppercase text-secondary">Ingresos</h6>
        <div class="d-flex justify-content-between">
            <span>Salario Bruto</span>
            <span id="salarioBruto">$0.00</span>
        </div>

        <hr>

        <h6 class="text-uppercase text-secondary">Deducciones</h6>

        <div class="d-flex justify-content-between text-danger">
            <span>ISSS (3%)</span>
            <span id="isss">$0.00</span>
        </div>

        <div class="d-flex justify-content-between text-danger">
            <span>AFP (7.25%)</span>
            <span id="afp">$0.00</span>
        </div>

        <hr>

        <h6 class="text-uppercase text-secondary">Renta Imponible</h6>
        <div class="d-flex justify-content-between">
            <span>Renta Neta Gravable</span>
            <span id="renta">$0.00</span>
        </div>

        <hr>

        <h6 class="text-uppercase text-secondary">Impuesto</h6>
        <div class="d-flex justify-content-between text-danger">
            <span>ISR Retenido</span>
            <span id="isr">$0.00</span>
        </div>

        <div class="alert alert-success d-flex justify-content-between fw-bold fs-5 mt-4">
            <span>SALARIO LÍQUIDO A RECIBIR</span>
            <span id="liquido">$0.00</span>
        </div>

    </div>
</div>

<!-- ===================== -->
<!-- MÓDULO CONTABLE -->
<!-- ===================== -->

<div class="card mt-5 shadow">
    <div class="card-header bg-transparent">
        REGISTRAR NUEVA PARTIDA
    </div>

    <div class="card-body">

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Describe la operación:</label>
                <input type="text" name="descripcion" class="form-control"
                 placeholder="Ej: compre computadoras por $2000" required>
            </div>

            <button class="btn btn-success">Guardar partida</button>
        </form>

    </div>
</div>

<!-- ===================== -->
<!-- LIBRO DIARIO -->
<!-- ===================== -->

<div class="card mt-4 shadow">
    <div class="card-header bg-transparent">
        LIBRO DIARIO - PARTIDAS GUARDADAS
    </div>

    <div class="card-body">

        <table class="table table-bordered table-striped">
            <thead class="table-secondary">
                <tr>
                    <th>Partida</th>
                    <th>Código</th>
                    <th>Cuenta</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $partidaActual = 0;

foreach($_SESSION['libro'] as $fila) {

    if ($partidaActual != $fila['partida']) {
        $partidaActual = $fila['partida'];

        echo "<tr>
    <td colspan='5'>
        <strong>PARTIDA {$fila['partida']}</strong><br>
        Operación: {$fila['operacion']}
    </td>
    <td>
        <button 
    class='btn btn-danger btn-sm'
    data-bs-toggle='modal'
    data-bs-target='#modalEliminar'
    data-partida='{$fila['partida']}'>
    Eliminar
</button>
    </td>
</tr>";

    }

    echo "<tr>
    <td>{$fila['partida']}</td>
    <td>{$fila['codigo']}</td>
    <td>{$fila['cuenta']}</td>
    <td>$".number_format($fila['debe'],2)."</td>
    <td>$".number_format($fila['haber'],2)."</td>
    <td>
    </td>
</tr>";
}
            ?>

</tbody>
</table>

    </div>
</div>

</div>
</div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      
      <div class="modal-header">
        <h5 class="modal-title">Confirmar eliminación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body">
        ¿Seguro que deseas eliminar esta partida?
      </div>
      
      <div class="modal-footer">
        <form method="POST">
          <input type="hidden" name="eliminar_partida" id="inputEliminar">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="submit" class="btn btn-danger">
            Confirmar
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
var modalEliminar = document.getElementById('modalEliminar');

modalEliminar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var partida = button.getAttribute('data-partida');
    document.getElementById('inputEliminar').value = partida;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

    </d