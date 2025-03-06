document.addEventListener("DOMContentLoaded", () => {
  // Recuperar datos del usuario desde sessionStorage
  const user = JSON.parse(sessionStorage.getItem("user"));

  // Redirigir si no hay usuario en sessionStorage
  if (!user) {
    alert("No tienes una sesión activa. Serás redirigido al login.");
    window.location.href = "./login.html";
    return;
  }

  // Cerrar sesión
  document.getElementById("logout").addEventListener("click", async () => {
    try {
      // Cambiar el endpoint para que llame al controlador de logout
      const response = await fetch(
        "../controllers/LoginController.php?action=logout",
        {
          method: "POST",
        }
      );
      if (response.ok) {
        sessionStorage.removeItem("user");
        window.location.href = "./login.html";
      } else {
        alert("No se pudo cerrar la sesión. Intenta de nuevo.");
      }
    } catch (error) {
      alert("Error al cerrar la sesión: " + error.message);
    }
  });

  function loadFacturas() {
    fetch("../models/getFacturas.php?")
      .then((response) => response.json())
      .then((data) => {
        const tableBody = document.getElementById("facturasTableBody");
        tableBody.innerHTML = ""; // Limpia cualquier contenido previo

        // Verifica si se obtuvieron facturas
        if (data.error) {
          tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.error}</td></tr>`;
          return;
        }

        if (data.length === 0) {
          tableBody.innerHTML = `<tr><td colspan="6" class="text-center">No se encontraron facturas</td></tr>`;
          return;
        }

        // Recorre cada factura y crea una fila en la tabla
        data.forEach((factura) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
              <td>${factura.FOLIO}</td>
              <td>${factura.FECHA_FACTURA}</td>
              <td>${factura.RFC_EMISOR}</td>
              <td>${factura.RFC_RECEPTOR}</td>
              <td>${factura.METODO_PAGO}</td>
              <td>${factura.TOTAL}</td>
              <td class="text-center">
                <button class="btn btn-primary btn-descargarPDF" data-id="${factura.REPOSITORIO_ID}" onClick="upload(${factura.REPOSITORIO_ID})" >Descargar PDF</button>
            </td>
            `;
          tableBody.appendChild(tr);
        });
      })
      .catch((error) => {
        console.error("Error al cargar facturas:", error);
      });
  }

  // Llama a la función para cargar las facturas
  loadFacturas();

});


$(document).ready(function () {
  $("#alertError").hide();
  $("#alertSuccess").hide();
});

//Variables para editar datos fiscales
let rfc = "STR191030S77";
let nombreCompleto = "SUC TRANSPORTES";
let calle = "ALBERTO N SWAIN";
let colonia = "CD INDUSTRIAL";
let numeroExt = "700-D";
let cp = "35078";
let pais = "27056";
let estado = "COAHUILA";
let ciudad = "TORREON";
let tipoPersona = "Moral";
let regimenFiscal = "601";
let usoCFDI = "G03";

let user = JSON.parse(sessionStorage.getItem("user"));
let usuario_nombre = user.nombre;

// Asignar los valores al formulario
document.getElementById("RFC").value = rfc;
document.getElementById("nombreF").value = nombreCompleto;
document.getElementById("calleF").value = calle;
document.getElementById("coloniaF").value = colonia;
document.getElementById("numeroF").value = numeroExt;
document.getElementById("cpF").value = cp;
document.getElementById("paisF").value = pais;
document.getElementById("estadoF").value = estado;
document.getElementById("ciudadF").value = ciudad;
document.getElementById("personaF").value = tipoPersona;
document.getElementById("regimenF").value = regimenFiscal;
document.getElementById("usosCFDI").value = usoCFDI;

function limpiarCampos() {
  document.getElementById("Notas").value = ""; // Limpia el campo de notas
  document.getElementById("Importe").value = ""; // Limpia el campo de importe
}



async function validarGuardarDF() {

  let notas = document.getElementById("Notas").value;
  console.log("Notas: ",notas);
  //console.log("validarGuardarDF");
  

  if(notas === null || notas === "")
  {
    if (!confirm("El campo Notas esta vacio, ¿Estás seguro que desas continuar?")) {
      return; // Si el usuario cancela, se detiene la ejecución
    }
  }
  else{
    //Solicitar confirmación antes de crear la factura
    if (!confirm("¿Estás seguro de crear la factura?")) {
      return; // Si el usuario cancela, se detiene la ejecución
    }
  }


  // Limpiar y ocultar cualquier mensaje de error previo
  $("#alertError").empty().hide("fast");

  // Cambiar el texto del botón mientras se procesa
  $("#btnDatosFiscales").html(
    'Creando factura <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>'
  );

  //let rutaGuardar = "../controllers/SelladoTimbrado.php?action=guardarDatosFacturacion";

  const rutaValidar = await fetch(`../controllers/SelladoTimbrado.php?action=crearCFDI40`);
  let importe = parseFloat(document.getElementById("Importe").value) || 0;
 

  let datosForm = new FormData();

  if (importe > 0) 
  {    
      datosForm.append("RFC", rfc);
      datosForm.append("NOMBRE_FAC", nombreCompleto);
      datosForm.append("CALLE_FAC", calle);
      datosForm.append("NUMERO_FAC", numeroExt);
      datosForm.append("CP_FAC", cp);
      datosForm.append("PERSONA_FAC", tipoPersona);
      datosForm.append("REGIMEN_DES_FAC", "XXXX");
      datosForm.append("REGIMEN_FAC", regimenFiscal);
      datosForm.append("USO_CFDI", usoCFDI);
      datosForm.append("FOLIO_TRANSACCION", "XXXXX");
      datosForm.append("CLIENTE_ID_DATOS_FISCALES", "111");
      datosForm.append("DATOS_FACTURA_MODIFICADOS", "S");
      datosForm.append("IMPORTE", importe);
      datosForm.append("USUARIO", usuario_nombre);
      datosForm.append("NOTAS", notas);

      $.ajax({
        url: rutaValidar.url,
        type: "POST",
        data: datosForm,
        processData: false,
        contentType: false,
        success: function (respuesta) {
          console.log(respuesta);
          if (respuesta.includes("XML timbrado guardado correctamente en:")) {
            alert("Factura creada correctamente.");
            limpiarCampos();
            // Restaurar el texto original del botón para permitir otra factura
            $("#btnDatosFiscales").html(
              `<span class="btn-texto"><i class="bi bi-file-earmark-plus"></i> Crear factura</span>`
            );
            $("#alertSuccess").show("fast");
          } else if (respuesta.includes("Error al timbrar:")) {
            $("#alertError").append(respuesta).show("fast");
            $("#btnDatosFiscales").html(
              `<span class="btn-texto"><i class="bi bi-file-earmark-plus"></i> Crear factura</span>`
            );

          }else if(respuesta.includes("Error en la petición cURL:")) {
            $("#alertError").append(respuesta).show("fast");
            $("#btnDatosFiscales").html(
              `<span class="btn-texto"><i class="bi bi-file-earmark-plus"></i> Crear factura</span>`
            );
          }
          
          else {
            console.error("Formato de respuesta no reconocido:", respuesta);
            $("#alertError").text("Formato de respuesta no reconocido");
            $("#alertError").show("fast");
          }
        },
        error: function (mensaje) {
          console.log("Error AJAX:", mensaje);
          $("#btnDatosFiscales").empty();
          $("#btnDatosFiscales").append(
            `<span class="btn-texto"><i class="bi bi-save2"></i> Guardar datos fiscales</span>`
          );
          alert("No se pueden verificar tus datos fiscales en este momento.");
        },
      });
    
    
  } else {
    $("#alertError").text(
      "El valor del campo importe debe ser mayor que cero (0)"
    );
    $("#alertError").show("fast");
  }
}

async function upload(folio){
  //console.log("btn pdf");

  if (!folio || isNaN(folio)) {
    console.error("Folio inválido o no encontrado");
    return;
}
  
  // Limpiar y ocultar cualquier mensaje de error previo
  $("#alertError").empty().hide("fast");


  fetch(`../controllers/FileUpload.php?action=buscarXML&folio=${folio}`)
  .then(response => {
    if (response.ok) {
      return response.text(); // O .json() si la respuesta es un JSON
    } else {
      throw new Error('Error al obtener los datos');
    }
  })
  .then(data => {
    let jsonData = JSON.parse(data);
    window.open(jsonData.data, "_blank");
  })
  .catch(error => {
    console.error('Error:', error);
  });
}
