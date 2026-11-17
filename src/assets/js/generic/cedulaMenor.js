import { executePetition, alertError, alertSuccess } from "./funtionGeneric.js";

/**
 * Genera la cédula de un menor a partir de la cédula del representante.
 * El formato es "12345678-R1": la parte numérica identifica al representante
 * y el sufijo -R indica que es la referencia de un menor de edad.
 */
export const inicializarBotonCedulaMenor = () => {
  const boton = document.getElementById("btnCedulaMenor");
  if (!boton || boton.dataset.listener === "true") return;

  boton.dataset.listener = "true";
  boton.addEventListener("click", async () => {
    const { value: cedulaRepresentante } = await Swal.fire({
      title: "Cédula del representante",
      input: "text",
      inputLabel: "Ingrese la cédula del representante adulto",
      inputPlaceholder: "Ej: 12345678",
      inputAttributes: {
        inputmode: "numeric",
        maxlength: "8",
        autocomplete: "off",
      },
      showCancelButton: true,
      confirmButtonText: "Generar",
      cancelButtonText: "Cancelar",
      inputValidator: (valor) =>
        /^[1-9][0-9]{6,7}$/.test(String(valor ?? "").trim())
          ? null
          : "Ingrese una cédula válida de 7 u 8 dígitos.",
    });

    if (!cedulaRepresentante) return;

    try {
      const datos = new FormData();
      datos.append("cedulaRepresentante", String(cedulaRepresentante).trim());

      const resultado = await executePetition(
        "/Pacientes/generarCedulaMenor",
        "POST",
        datos,
      );

      if (!resultado?.ok || !resultado.cedula) {
        throw new Error(
          resultado?.error ?? "No se pudo generar la cédula del menor.",
        );
      }

      const input = document.getElementById("cedulaPaciente");
      if (input) {
        input.value = resultado.cedula;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("keyup", { bubbles: true }));
      }

      alertSuccess(`Cédula de menor generada: ${resultado.cedula}`);
    } catch (error) {
      alertError("Error", error.message ?? String(error));
    }
  });
};
