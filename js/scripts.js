let cart = [];
let selectedSnacks = new Set(); // Mantenemos el Set para tracking local

document.addEventListener('DOMContentLoaded', function() {
    // Añadir un data attribute en la fila de la función para almacenar el precio
    const funcionRows = document.querySelectorAll('[data-funcion-id]');
    funcionRows.forEach(row => {
        // Obtener el precio del ticket de la película actual
        fetch(`../controlador/obtener_precio.php?id_pelicula=${row.dataset.peliculaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.dataset.ticketPrice = data.precio;
                }
            })
            .catch(error => console.error('Error:', error));
    });
});


function addToCart(funcionId, id, name, price, isSnack = false) {
    let cartForFunction = cart.find(c => c.funcionId === funcionId);
    if (!cartForFunction) {
        cartForFunction = { 
            funcionId, 
            items: [],
            snacks: [] // Nuevo array específico para aperitivos
        };
        cart.push(cartForFunction);
    }

    if (isSnack) {
        // Agregar el aperitivo al array específico de snacks
        const existingSnack = cartForFunction.snacks.find(snack => snack.id === id);
        if (existingSnack) {
            existingSnack.quantity += 1;
        } else {
            cartForFunction.snacks.push({
                id,
                name,
                price,
                quantity: 1
            });
        }
        selectedSnacks.add(id);
    } else {
        // Lógica existente para tickets
        const existingItemIndex = cartForFunction.items.findIndex(item => item.id === id);
        if (existingItemIndex !== -1) {
            cartForFunction.items[existingItemIndex].quantity += 1;
        } else {
            cartForFunction.items.push({ 
                id, 
                name, 
                price,
                quantity: 1,
                type: 'ticket'
            });
        }
    }

    updateCartForFunction(funcionId);
}

function removeFromCart(funcionId, id, isSnack = false) {
    const cartForFunction = cart.find(c => c.funcionId === funcionId);
    
    if (isSnack) {
        const snackIndex = cartForFunction.snacks.findIndex(snack => snack.id === id);
        if (snackIndex !== -1) {
            cartForFunction.snacks.splice(snackIndex, 1);
        }
    } else {
        const index = cartForFunction.items.findIndex(item => item.id === id);
        if (index !== -1) {
            cartForFunction.items.splice(index, 1);
        }
    }
    
    updateCartForFunction(funcionId);
}

function updateCartForFunction(funcionId) {
    const cartForFunction = cart.find(c => c.funcionId === funcionId);
    const cartDetails = document.getElementById('cart-details-' + funcionId);
    const totalPriceElement = document.getElementById('total-price-' + funcionId);
    cartDetails.innerHTML = '';
    let totalPrice = 0;

    if (cartForFunction) {
        // Mostrar tickets
        cartForFunction.items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            totalPrice += itemTotal;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name} (x${item.quantity})</td>
                <td>$${item.price.toFixed(2)}</td>
                <td>
                    <span>Por favor quite el asiento del mapa para borrar la entrada del carrito</span>
                </td>
            `;
            cartDetails.appendChild(row);
        });

        // Mostrar aperitivos
        cartForFunction.snacks.forEach(snack => {
            const snackTotal = snack.price * snack.quantity;
            totalPrice += snackTotal;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${snack.name} (x${snack.quantity})</td>
                <td>$${snack.price.toFixed(2)}</td>
                <td>
                    <button onclick="removeFromCart(${funcionId}, ${snack.id}, true)">Eliminar</button>
                </td>
            `;
            cartDetails.appendChild(row);
        });
    }

    totalPriceElement.innerHTML = `TOTAL: $${totalPrice.toFixed(2)}`;
}

function toggleDetails(element, funcionId) {
    const row = element.closest('tr').nextElementSibling;
    row.classList.toggle('open');
    updateCartForFunction(funcionId);
}

function toggleSeat(seatElement, funcionId) {
    if (seatElement.dataset.disponible === 'true') {
        seatElement.classList.toggle('selected');

        const seatId = seatElement.dataset.id;
        const seatName = `Entrada ${seatElement.dataset.fila}${seatElement.dataset.numero}`;
        
        // Obtener el precio del ticket de la fila de la función
        const funcionRow = document.querySelector(`[data-funcion-id="${funcionId}"]`);
        const ticketPrice = parseFloat(funcionRow.dataset.ticketPrice);

        if (seatElement.classList.contains('selected')) {
            addToCart(funcionId, seatId, seatName, ticketPrice);
        } else {
            removeFromCart(funcionId, seatId);
        }

        updateSelectedSeats(funcionId);
    }
}


function updateSelectedSeats(funcionId) {
    const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'));
    const seatValues = selectedSeats.map(seat => seat.dataset.id); // Ahora estamos extrayendo el ID real de cada asiento
    
    document.querySelector(`#asientos-${funcionId}`).value = JSON.stringify(seatValues);
}


document.querySelectorAll('.checkout-btn').forEach((button) => {
    button.addEventListener('click', async function() {
        try {
            const cartSection = this.closest('section.cart');
            if (!cartSection) {
                throw new Error('No se pudo encontrar la sección del carrito');
            }

            const funcionId = cartSection.id.replace('cart-', '');
            const cartForFunction = cart.find(c => c.funcionId === parseInt(funcionId));

            if (!cartForFunction || (!cartForFunction.items.length && !cartForFunction.snacks.length)) {
                throw new Error('El carrito está vacío');
            }

            const asientosInput = document.getElementById(`asientos-${funcionId}`);
            let asientos = [];
            if (asientosInput) {
                try {
                    asientos = JSON.parse(asientosInput.value);
                } catch (e) {
                    console.error('Error al parsear asientos JSON:', e);
                    throw new Error('Asientos seleccionados no válidos');
                }
            }

            if (asientos.length === 0) {
                throw new Error('No se han seleccionado asientos');
            }

            // Preparar datos para enviar al servidor
            const cartData = {
                funcionId: parseInt(funcionId),
                asientos: asientos.map(asientoId => ({ id: asientoId })),
                aperitivos: cartForFunction.snacks.map(snack => ({
                    id: snack.id,
                    cantidad: snack.quantity,
                    nombre: snack.name,
                    precio: snack.price
                })),
                items: cartForFunction.items.map(item => ({
                    name: item.name,
                    quantity: item.quantity,
                    price: item.price,
                }))
            };

            console.log('Enviando datos al servidor:', cartData);

            const response = await fetch('../controlador/crear-preferencia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(cartData)
            });

            const responseText = await response.text();
            console.log('Respuesta del servidor (texto):', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Error al parsear respuesta JSON:', responseText);
                throw new Error('La respuesta del servidor no es JSON válido');
            }

            if (!response.ok) {
                throw new Error(data.message || 'Error del servidor');
            }

            if (data.status !== 'success' || !data.id) {
                throw new Error('Respuesta inválida del servidor');
            }

            window.location.href = `https://www.mercadopago.com/mla/checkout/start?pref_id=${data.id}`;

        } catch (error) {
            console.error('Error detallado:', {
                message: error.message,
                stack: error.stack,
                error: error
            });
            alert(`Error: ${error.message}`);
        }
    });
});

