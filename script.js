function calcular() {
    let salario = parseFloat(document.getElementById("salario").value);

    if (isNaN(salario) || salario <= 0) {
        alert("Ingrese un salario válido");
        return;
    }

    // ISSS
    let isss = salario * 0.03;
    if (salario >= 1000) {
        isss = 30;
    }

    // AFP
    let afp = salario * 0.0725;

    // Renta imponible
    let renta = salario - isss - afp;

    // ISR
    let isr = 0;

    if (renta <= 550) {
        isr = 0;
    } else if (renta <= 895.24) {
        isr = (renta - 550) * 0.10 + 17.67;
    } else if (renta <= 2038.10) {
        isr = (renta - 895.24) * 0.20 + 60;
    } else {
        isr = (renta - 2038.10) * 0.30 + 288.57;
    }

    // Líquido
    let liquido = salario - isss - afp - isr;

    // Mostrar resultados
    document.getElementById("isss").textContent = `$${isss.toFixed(2)}`;
    document.getElementById("afp").textContent = `$${afp.toFixed(2)}`;
    document.getElementById("renta").textContent = `$${renta.toFixed(2)}`;
    document.getElementById("isr").textContent = `$${isr.toFixed(2)}`;
    document.getElementById("liquido").textContent = `$${liquido.toFixed(2)}`;
    document.getElementById("salarioBruto").textContent = `$${salario.toFixed(2)}`;

}
