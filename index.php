<?php
session_start();

if (!isset($_SESSION['libro'])) {
    $_SESSION['libro'] = [];
}

/* ===========================
   ELIMINAR PARTIDA
=========================== */
if (isset($_POST['eliminar_partida'])) {

    $partidaEliminar = $_POST['eliminar_partida'];

    $_SESSION['libro'] = array_filter($_SESSION['libro'], function($fila) use ($partidaEliminar) {
        return $fila['partida'] != $partidaEliminar;
    });

    header("Location: ?vista=libro");
    exit();
}

/* ===========================
   FUNCIÓN GUARDAR
=========================== */
function guardar($partida,$descripcion,$codigo,$cuenta,$debe,$haber) {
    $_SESSION['libro'][] = [
        "partida"=>$partida,
        "descripcion"=>$descripcion,
        "codigo"=>$codigo,
        "cuenta"=>$cuenta,
        "debe"=>$debe,
        "haber"=>$haber
    ];
}

/* ===========================
   REGISTRO CONTABLE
=========================== */
if (isset($_POST['descripcion'])) {

    $descripcionOriginal = $_POST['descripcion'];
    $descripcion = strtolower($descripcionOriginal);

    $partida = count($_SESSION['libro']) > 0 
        ? max(array_column($_SESSION['libro'], 'partida')) + 1 
        : 1;

    preg_match('/\$(\d+(\.\d+)?)/', $descripcion, $montoMatch);
    $monto = $montoMatch[1] ?? 0;

    if ($monto > 0) {

        $iva = round($monto * 0.13 / 1.13, 2);
        $base = round($monto - $iva, 2);

        // 1 Apertura
        if (strpos($descripcion,"apertura") !== false || strpos($descripcion,"capital") !== false) {
            guardar($partida,$descripcionOriginal,'1101','Efectivo',$monto,0);
            guardar($partida,$descripcionOriginal,'3101','Capital Social',0,$monto);
        }

        // 2 Compra mobiliario
        elseif (strpos($descripcion,"mobiliario") !== false || strpos($descripcion,"equipo") !== false) {
            guardar($partida,$descripcionOriginal,'1201','Mobiliario y Equipo',$base,0);
            guardar($partida,$descripcionOriginal,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcionOriginal,'1101','Efectivo',0,$monto);
        }

        // 3 Compra mercancía crédito
        elseif (strpos($descripcion,"compra") !== false && strpos($descripcion,"credito") !== false) {
            guardar($partida,$descripcionOriginal,'1105','Inventario',$base,0);
            guardar($partida,$descripcionOriginal,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcionOriginal,'2101','Proveedores',0,$monto);
        }

        // 4 Venta contado
        elseif (strpos($descripcion,"venta") !== false && strpos($descripcion,"contado") !== false) {
            guardar($partida,$descripcionOriginal,'1101','Efectivo',$monto,0);
            guardar($partida,$descripcionOriginal,'4101','Ventas',0,$base);
            guardar($partida,$descripcionOriginal,'2102','IVA Débito Fiscal',0,$iva);
        }

        // 5 Venta crédito
        elseif (strpos($descripcion,"venta") !== false && strpos($descripcion,"credito") !== false) {
            guardar($partida,$descripcionOriginal,'1103','Cuentas por Cobrar',$monto,0);
            guardar($partida,$descripcionOriginal,'4101','Ventas',0,$base);
            guardar($partida,$descripcionOriginal,'2102','IVA Débito Fiscal',0,$iva);
        }

        // 6 Pago proveedores
        elseif (strpos($descripcion,"pago") !== false && strpos($descripcion,"proveedor") !== false) {
            guardar($partida,$descripcionOriginal,'2101','Proveedores',$monto,0);
            guardar($partida,$descripcionOriginal,'1101','Efectivo',0,$monto);
        }

        // 7 Devolución compra
        elseif (strpos($descripcion,"devolucion") !== false) {
            guardar($partida,$descripcionOriginal,'2101','Proveedores',$monto,0);
            guardar($partida,$descripcionOriginal,'1105','Inventario',0,$base);
            guardar($partida,$descripcionOriginal,'1104','IVA Crédito Fiscal',0,$iva);
        }

        // 8 Alquiler
        elseif (strpos($descripcion,"alquiler") !== false) {
            guardar($partida,$descripcionOriginal,'5101','Gasto de Alquiler',$base,0);
            guardar($partida,$descripcionOriginal,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcionOriginal,'1101','Efectivo',0,$monto);
        }

        // 9 Cobro clientes
        elseif (strpos($descripcion,"cobro") !== false || strpos($descripcion,"recib") !== false) {
            guardar($partida,$descripcionOriginal,'1101','Efectivo',$monto,0);
            guardar($partida,$descripcionOriginal,'1103','Cuentas por Cobrar',0,$monto);
        }

        // 10 Publicidad
        elseif (strpos($descripcion,"publicidad") !== false) {
            guardar($partida,$descripcionOriginal,'5103','Gasto de Publicidad',$base,0);
            guardar($partida,$descripcionOriginal,'1104','IVA Crédito Fiscal',$iva,0);
            guardar($partida,$descripcionOriginal,'1101','Efectivo',0,$monto);
        }

        header("Location: ?vista=libro");
        exit();
    }
}

$vista = $_GET['vista'] ?? 'calculadora';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sistema Contable</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#DED5D9;
    color:#fff;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}
.navbar{
    background:#8717FF !important;
}
.navbar a{
    color:#fff !important;
    font-weight:bold;

}
.card{
    background:#F2EBED;
    color: #222;
    border-radius:20px;
    margin:auto;
}
.form-control{
    background:#fff;
    border:3px solid #333;
    color:#000;
}
.form-control::placeholder{
    color:#777;
}
.btn-primary{
    background:#A352FA;
    border:2px solid #6C7070;
    color: #fff
}
.btn-primary:hover{
    background:#6C7070;
}
.table{
    color:#fff;
}
.table thead{
    background:#111;
}
footer{
    background:#8717FF;
    color:#fff;
    text-align:center;
    padding:20px;
    font-weight:bold;

}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg">
<div class="container">
<a class="navbar-brand text-white" href="#">Sistema Contable</a>
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link" href="?vista=calculadora">Calculadora</a></li>
<li class="nav-item"><a class="nav-link" href="?vista=libro">Libro Diario</a></li>
</ul>
</div>
</nav>

<div class="container py-5 flex-fill">


<?php if($vista == 'calculadora'): ?>

<div class="card p-4">
<h4>Calculadora Salarial SV</h4>

<input type="number" id="salario" class="form-control my-3" placeholder="Ingrese salario mensual">

<button class="btn btn-primary w-100" onclick="calcular()">Calcular</button>

<div id="resultado" class="mt-4"></div>
</div>

<?php endif; ?>

<?php if($vista == 'libro'): ?>

<div class="card p-4 mb-4">
<h5>Registrar Operación</h5>
<form method="POST">
<input type="text" name="descripcion" class="form-control mb-3"
placeholder="Ej: Venta al contado $8000" required>
<button class="btn btn-primary">Guardar</button>
</form>
</div>

<div class="card p-4">
<h5>Libro Diario</h5>

<table class="table table-bordered">
<thead>
<tr>
<th>Partida</th>
<th>Código</th>
<th>Cuenta</th>
<th>Debe</th>
<th>Haber</th>
<th></th>
</tr>
</thead>
<tbody>

<?php
$partidaActual=0;

foreach($_SESSION['libro'] as $fila){

if($partidaActual!=$fila['partida']){
$partidaActual=$fila['partida'];

echo "<tr>
<td colspan='5'><strong>PARTIDA {$fila['partida']}</strong><br>{$fila['descripcion']}</td>
<td>
<form method='POST'>
<input type='hidden' name='eliminar_partida' value='{$fila['partida']}'>
<button class='btn btn-danger btn-sm'>Eliminar</button>
</form>
</td>
</tr>";
}

echo "<tr>
<td>{$fila['partida']}</td>
<td>{$fila['codigo']}</td>
<td>{$fila['cuenta']}</td>
<td>$".number_format($fila['debe'],2)."</td>
<td>$".number_format($fila['haber'],2)."</td>
<td></td>
</tr>";
}
?>

</tbody>
</table>
</div>

<?php endif; ?>

</div>

<footer>
Sistema Contable © 2026 - Daniel Vargas
</footer>

<script>
function calcular(){
let salario=parseFloat(document.getElementById("salario").value);
if(isNaN(salario)||salario<=0){alert("Ingrese salario válido");return;}

let isss=salario*0.03;
let afp=salario*0.0725;
let rentaNeta=salario-isss-afp;

let isr=0;
if(rentaNeta<=472){isr=0;}
else if(rentaNeta<=895.24){isr=(rentaNeta-472)*0.10+17.67;}
else if(rentaNeta<=2038.10){isr=(rentaNeta-895.24)*0.20+60;}
else{isr=(rentaNeta-2038.10)*0.30+288.57;}

let liquido=rentaNeta-isr;

document.getElementById("resultado").innerHTML=`
<hr>
Salario Bruto: $${salario.toFixed(2)}<br>
ISSS (3%): $${isss.toFixed(2)}<br>
AFP (7.25%): $${afp.toFixed(2)}<br>
ISR: $${isr.toFixed(2)}<br>
<h5 class='mt-3'>Sueldo líquido: $${liquido.toFixed(2)}</h5>
`;
}
</script>

</body>
</html>
