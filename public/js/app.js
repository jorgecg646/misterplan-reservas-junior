/**
 * Aplicación Frontend — Gestión de Reservas (MisterPlan)
 * JavaScript Vanilla para interacción dinámica con la API REST
 * Sin frameworks, código limpio, modular y comentado.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar todos los módulos cuando el DOM esté listo
    initFiltros();
    initModalCrear();
    initAccionesReservas();
});

/* ==========================================================================
   1. FILTRADO DINÁMICO DE RESERVAS (Sin recargar la página)
   ========================================================================== */

function initFiltros() {
    const filterForm = document.getElementById('filter-form');
    const inputSearch = document.getElementById('filter-search');
    const selectEstado = document.getElementById('filter-estado');
    const inputDesde = document.getElementById('filter-desde');
    const inputHasta = document.getElementById('filter-hasta');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');

    // Si no estamos en la página del listado, salir
    if (!filterForm) return;

    // Evitar que el formulario haga recarga tradicional al pulsar Enter
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        ejecutarFiltros();
    });

    // Búsqueda en vivo por nombre con 'debounce' para no saturar con peticiones al escribir
    let debounceTimer = null;
    inputSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            ejecutarFiltros();
        }, 300); // Espera 300ms tras la última tecla pulsada
    });

    // Filtrar automáticamente al cambiar estado o fechas
    selectEstado.addEventListener('change', ejecutarFiltros);
    inputDesde.addEventListener('change', ejecutarFiltros);
    inputHasta.addEventListener('change', ejecutarFiltros);

    // Botón de limpiar filtros
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', (e) => {
            e.preventDefault();
            filterForm.reset();
            ejecutarFiltros();
        });
    }
}

/**
 * Recopila los valores de los filtros y consulta la API
 */
async function ejecutarFiltros() {
    const search = document.getElementById('filter-search')?.value.trim() || '';
    const estado = document.getElementById('filter-estado')?.value || '';
    const desde = document.getElementById('filter-desde')?.value || '';
    const hasta = document.getElementById('filter-hasta')?.value || '';

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (estado) params.append('estado', estado);
    if (desde) params.append('fecha_desde', desde);
    if (hasta) params.append('fecha_hasta', hasta);

    mostrarCargandoTabla(true);

    try {
        const respuesta = await fetch(`/api/reservas?${params.toString()}`);
        if (!respuesta.ok) {
            throw new Error(`Error en el servidor (${respuesta.status})`);
        }

        const resultado = await respuesta.json();
        if (resultado.status === 'success') {
            repintarTabla(resultado.data);
            actualizarContador(resultado.data.length);
        } else {
            mostrarAlerta(resultado.message || 'Error al obtener las reservas', 'danger');
        }
    } catch (error) {
        console.error('Error al filtrar reservas:', error);
        mostrarAlerta('No se pudieron cargar las reservas. Comprueba la conexión.', 'danger');
    } finally {
        mostrarCargandoTabla(false);
    }
}

/**
 * Repinta el contenido de la tabla con los datos devueltos por la API
 */
function repintarTabla(reservas) {
    const tbody = document.getElementById('reservas-tbody');
    if (!tbody) return;

    if (!reservas || reservas.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-row">
                <td colspan="7" class="text-center py-4">
                    No se encontraron reservas con los filtros seleccionados.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = reservas.map(reserva => {
        // Formatear fechas a DD/MM/AAAA
        const fEntrada = formatearFecha(reserva.fecha_entrada);
        const fSalida = formatearFecha(reserva.fecha_salida);

        // Formatear importe a moneda europea
        const importe = parseFloat(reserva.importe).toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Configurar el badge según el estado
        let badgeHtml = '';
        if (reserva.estado === 'confirmada') {
            badgeHtml = '<span class="badge badge-success">Confirmada</span>';
        } else if (reserva.estado === 'pendiente') {
            badgeHtml = '<span class="badge badge-warning">Pendiente</span>';
        } else {
            badgeHtml = '<span class="badge badge-danger">Cancelada</span>';
        }

        // Botones de acción dinámicos
        const botonConfirmar = (reserva.estado === 'pendiente')
            ? `<button type="button" class="btn btn-sm btn-success btn-confirmar-reserva" data-id="${reserva.id}" title="Confirmar reserva">Confirmar</button>`
            : '';

        const botonCancelar = (reserva.estado !== 'cancelada')
            ? `<button type="button" class="btn btn-sm btn-danger btn-cancelar-reserva" data-id="${reserva.id}" title="Cancelar reserva">Cancelar</button>`
            : '';

        return `
            <tr id="fila-reserva-${reserva.id}">
                <td class="font-bold">#${reserva.id}</td>
                <td>
                    <div class="guest-name">${escaparHtml(reserva.nombre_huesped)}</div>
                    <div class="guest-email text-muted">${escaparHtml(reserva.email_huesped)}</div>
                </td>
                <td>${escaparHtml(reserva.nombre_alojamiento)}</td>
                <td>
                    <div><strong>Entrada:</strong> ${fEntrada}</div>
                    <div class="text-muted"><strong>Salida:</strong> ${fSalida}</div>
                </td>
                <td class="font-bold">${importe} €</td>
                <td>${badgeHtml}</td>
                <td class="text-right actions-cell">
                    <a href="/reservas/${reserva.id}" class="btn btn-sm btn-outline" title="Ver detalle e historial">
                        Ver Detalle
                    </a>
                    ${botonConfirmar}
                    ${botonCancelar}
                </td>
            </tr>
        `;
    }).join('');
}

function actualizarContador(total) {
    const contador = document.getElementById('total-reservas');
    if (contador) contador.textContent = total;
}

function mostrarCargandoTabla(mostrar) {
    const indicator = document.getElementById('table-loading');
    if (indicator) {
        indicator.style.display = mostrar ? 'inline-flex' : 'none';
    }
}


/* ==========================================================================
   2. MODAL Y CREACIÓN DE RESERVAS (Validación cliente + servidor)
   ========================================================================== */

function initModalCrear() {
    const modal = document.getElementById('modal-crear-reserva');
    const btnAbrir = document.getElementById('btn-nueva-reserva');
    const btnCerrar = document.getElementById('btn-cerrar-modal');
    const btnCancelar = document.getElementById('btn-cancelar-modal');
    const formCrear = document.getElementById('form-crear-reserva');

    if (!modal) return;

    // Abrir modal
    if (btnAbrir) {
        btnAbrir.addEventListener('click', () => {
            limpiarErroresModal();
            formCrear.reset();
            modal.style.display = 'flex';
            document.getElementById('nombre_huesped')?.focus();
        });
    }

    // Funciones para cerrar modal
    const cerrar = () => {
        modal.style.display = 'none';
        limpiarErroresModal();
    };

    if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrar);

    // Cerrar al hacer clic en el fondo oscuro
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrar();
    });

    // Envío del formulario con validación
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            limpiarErroresModal();

            // 1. Recopilar datos
            const formData = new FormData(formCrear);
            const datos = {
                nombre_huesped: formData.get('nombre_huesped')?.trim() || '',
                email_huesped: formData.get('email_huesped')?.trim() || '',
                nombre_alojamiento: formData.get('nombre_alojamiento')?.trim() || '',
                fecha_entrada: formData.get('fecha_entrada') || '',
                fecha_salida: formData.get('fecha_salida') || '',
                importe: formData.get('importe') || '',
                notas: formData.get('notas')?.trim() || '',
            };

            // 2. Validación en cliente (Feedback instantáneo)
            const erroresCliente = validarDatosCliente(datos);
            if (Object.keys(erroresCliente).length > 0) {
                mostrarErroresCampos(erroresCliente);
                return;
            }

            // 3. Envío al servidor
            activarBotonGuardar(true);

            try {
                const res = await fetch('/api/reservas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datos)
                });

                const respuesta = await res.json();

                if (res.status === 201 && respuesta.status === 'success') {
                    // Éxito: cerrar modal, mostrar aviso y refrescar tabla
                    cerrar();
                    mostrarAlerta('¡Reserva creada con éxito!', 'success');
                    ejecutarFiltros();
                } else if (res.status === 422 && respuesta.errors) {
                    // Error de validación devuelto por el servidor
                    mostrarErroresCampos(respuesta.errors);
                    mostrarAlertaModal('Por favor, corrige los errores señalados.');
                } else {
                    mostrarAlertaModal(respuesta.message || 'Error al procesar la reserva en el servidor.');
                }
            } catch (error) {
                console.error('Error de red al crear reserva:', error);
                mostrarAlertaModal('Error de conexión al intentar guardar la reserva.');
            } finally {
                activarBotonGuardar(false);
            }
        });
    }
}

/**
 * Validación rápida en cliente antes de hacer la petición
 */
function validarDatosCliente(datos) {
    const errores = {};

    if (!datos.nombre_huesped || datos.nombre_huesped.length < 3) {
        errores.nombre_huesped = 'El nombre debe tener al menos 3 caracteres.';
    }

    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!datos.email_huesped) {
        errores.email_huesped = 'El correo electrónico es obligatorio.';
    } else if (!regexEmail.test(datos.email_huesped)) {
        errores.email_huesped = 'El formato del correo electrónico no es válido.';
    }

    if (!datos.nombre_alojamiento || datos.nombre_alojamiento.length < 3) {
        errores.nombre_alojamiento = 'El nombre del alojamiento es obligatorio.';
    }

    if (!datos.fecha_entrada) {
        errores.fecha_entrada = 'Selecciona la fecha de entrada.';
    }

    if (!datos.fecha_salida) {
        errores.fecha_salida = 'Selecciona la fecha de salida.';
    }

    if (datos.fecha_entrada && datos.fecha_salida) {
        if (new Date(datos.fecha_salida) <= new Date(datos.fecha_entrada)) {
            errores.fecha_salida = 'La salida debe ser posterior a la fecha de entrada.';
        }
    }

    if (!datos.importe || isNaN(datos.importe) || parseFloat(datos.importe) <= 0) {
        errores.importe = 'Introduce un importe válido mayor a 0.';
    }

    return errores;
}

function mostrarErroresCampos(errores) {
    for (const [campo, mensaje] of Object.entries(errores)) {
        const errorDiv = document.getElementById(`error-${campo}`);
        const input = document.getElementById(campo);

        if (errorDiv) {
            errorDiv.textContent = mensaje;
            errorDiv.style.display = 'block';
        }
        if (input) {
            input.style.borderColor = 'var(--danger)';
        }
    }
}

function limpiarErroresModal() {
    document.querySelectorAll('.field-error').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
    document.querySelectorAll('#form-crear-reserva .form-control').forEach(input => {
        input.style.borderColor = 'var(--border-color)';
    });
    const alertBox = document.getElementById('modal-error-alert');
    if (alertBox) alertBox.style.display = 'none';
}

function mostrarAlertaModal(mensaje) {
    const alertBox = document.getElementById('modal-error-alert');
    if (alertBox) {
        alertBox.textContent = mensaje;
        alertBox.style.display = 'block';
    }
}

function activarBotonGuardar(cargando) {
    const btn = document.getElementById('btn-guardar-reserva');
    if (!btn) return;

    btn.disabled = cargando;
    const textSpan = btn.querySelector('.btn-text');
    const spinnerSpan = btn.querySelector('.btn-spinner');

    if (textSpan) textSpan.style.display = cargando ? 'none' : 'inline';
    if (spinnerSpan) spinnerSpan.style.display = cargando ? 'inline' : 'none';
}


/* ==========================================================================
   3. ACCIONES: CONFIRMAR Y CANCELAR RESERVAS
   ========================================================================== */

function initAccionesReservas() {
    // Delegación de eventos en el documento para botones creados dinámicamente
    document.addEventListener('click', async (e) => {
        // 1. Confirmar reserva
        const btnConfirmar = e.target.closest('.btn-confirmar-reserva');
        if (btnConfirmar) {
            const id = btnConfirmar.getAttribute('data-id');
            const redirect = btnConfirmar.getAttribute('data-redirect') === 'true';
            await cambiarEstadoReserva(id, 'confirmada', redirect);
            return;
        }

        // 2. Cancelar reserva
        const btnCancelar = e.target.closest('.btn-cancelar-reserva');
        if (btnCancelar) {
            const id = btnCancelar.getAttribute('data-id');
            const redirect = btnCancelar.getAttribute('data-redirect') === 'true';

            const confirmacion = confirm(`¿Estás seguro/a de que deseas cancelar la reserva #${id}?`);
            if (confirmacion) {
                await cambiarEstadoReserva(id, 'cancelada', redirect);
            }
        }
    });
}

/**
 * Petición a la API para cambiar el estado
 */
async function cambiarEstadoReserva(id, nuevoEstado, redirect) {
    try {
        const respuesta = await fetch(`/api/reservas/${id}/estado`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ estado: nuevoEstado })
        });

        const datos = await respuesta.json();

        if (respuesta.ok && datos.status === 'success') {
            const mensaje = (nuevoEstado === 'confirmada')
                ? `¡Reserva #${id} confirmada con éxito!`
                : `Reserva #${id} cancelada correctamente.`;

            mostrarAlerta(mensaje, 'success');

            if (redirect) {
                // Si estamos en la página de detalle, recargamos para ver el nuevo estado y el evento en el timeline
                setTimeout(() => window.location.reload(), 800);
            } else {
                // Si estamos en el listado, repintamos la tabla sin recargar la página
                ejecutarFiltros();
            }
        } else {
            mostrarAlerta(datos.message || `No se pudo cambiar el estado de la reserva #${id}`, 'danger');
        }
    } catch (error) {
        console.error('Error al cambiar estado:', error);
        mostrarAlerta('Error de red al actualizar la reserva.', 'danger');
    }
}


/* ==========================================================================
   4. UTILIDADES GENERALES
   ========================================================================== */

/**
 * Muestra alertas visuales temporales en la parte superior
 */
function mostrarAlerta(mensaje, tipo = 'success') {
    const contenedor = document.getElementById('alert-container');
    if (!contenedor) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${tipo}`;
    alert.innerHTML = `
        <span>${escaparHtml(mensaje)}</span>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:1.2rem;line-height:1;" onclick="this.parentElement.remove()">&times;</button>
    `;

    contenedor.innerHTML = '';
    contenedor.appendChild(alert);

    // Auto-eliminar la alerta a los 4 segundos
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 4000);
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const partes = fechaStr.split('-');
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaStr;
}

function escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}
