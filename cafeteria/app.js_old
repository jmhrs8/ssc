let currentActiveTable = null;
let activeTicketItems = [];
let weeklySalesChartInstance = null;

// Cargar reportes al iniciar si la pestaña de reportes está activa
document.addEventListener("DOMContentLoaded", () => {
    const reportsTab = document.getElementById('tab-reports');
    if (reportsTab && !reportsTab.classList.contains('hidden')) {
        loadManagerReports();
    }
});

// Refrescar KDS y alertas automáticamente cada 3 segundos
setInterval(() => {
    const kdsTab = document.getElementById('tab-kds');
    if (kdsTab && !kdsTab.classList.contains('hidden')) {
        loadKDSOrders();
    }
    const posTab = document.getElementById('tab-pos');
    if (posTab && !posTab.classList.contains('hidden')) {
        checkReadyItemsForWaiters();
    }
}, 3000);

function selectTable(id, name, status) {
    currentActiveTable = { id, name, status };
    document.getElementById('active-target-badge').innerText = `Mesa: ${name}`;

    if (status === 'ocupada' || status === 'cuenta_solicitada') {
        loadTableExistingBill(id);
    } else {
        activeTicketItems = [];
        renderTicketUI();
    }
}

async function loadTableExistingBill(tableId) {
    try {
        const res = await fetch(`api.php?action=get_table_bill&table_id=${tableId}`);
        const data = await res.json();

        if (data.success) {
            activeTicketItems = data.order.items.map(i => ({
                id: i.product_id,
                name: i.product_name,
                price: parseFloat(i.unit_price),
                quantity: parseInt(i.quantity),
                notes: i.notes || '',
                is_saved: true
            }));

            if (data.order.client_name) {
                document.getElementById('takeout-client-name').value = data.order.client_name;
            }

            renderTicketUI(data.order.id);
        }
    } catch (e) {
        console.error("Error al cargar cuenta:", e);
    }
}

function addToTicket(id, name, price) {
    const existing = activeTicketItems.find(item => item.id === id && !item.is_saved);
    if (existing) {
        existing.quantity++;
    } else {
        activeTicketItems.push({ id, name, price, quantity: 1, notes: '', is_saved: false });
    }
    renderTicketUI();
}

function renderTicketUI(existingOrderId = null) {
    const container = document.getElementById('ticket-items-container');
    if (!container) return;
    
    if (activeTicketItems.length === 0) {
        container.innerHTML = `<p class="text-center text-sm text-slate-500 py-8">Ningún producto seleccionado.</p>`;
        document.getElementById('ticket-subtotal').innerText = '$0.00';
        document.getElementById('ticket-iva').innerText = '$0.00';
        document.getElementById('ticket-total').innerText = '$0.00';
        return;
    }

    let html = '';
    let subtotal = 0;

    activeTicketItems.forEach((item, index) => {
        let totalItem = item.price * item.quantity;
        subtotal += totalItem;
        
        const badgeSaved = item.is_saved ? `<span class="text-[10px] bg-slate-700 text-amber-300 px-1.5 py-0.5 rounded mr-1">En Cocina</span>` : `<span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded mr-1">Nuevo</span>`;

        html += `
            <div class="flex justify-between items-center bg-slate-800/80 p-2.5 rounded-xl border border-slate-700/80">
                <div>
                    <h5 class="text-xs font-bold text-slate-200 flex items-center">${badgeSaved} ${item.name}</h5>
                    <span class="text-xs text-amber-400">$${item.price.toFixed(2)} x ${item.quantity}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-bold text-slate-100">$${totalItem.toFixed(2)}</span>
                    ${!item.is_saved ? `<button onclick="removeTicketItem(${index})" class="text-rose-400 hover:text-rose-300 px-1"><i class="fa-solid fa-trash text-xs"></i></button>` : ''}
                </div>
            </div>
        `;
    });

    let iva = subtotal * 0.16;
    let total = subtotal + iva;

    container.innerHTML = html;
    document.getElementById('ticket-subtotal').innerText = `$${subtotal.toFixed(2)}`;
    document.getElementById('ticket-iva').innerText = `$${iva.toFixed(2)}`;
    document.getElementById('ticket-total').innerText = `$${total.toFixed(2)}`;
    
    container.dataset.activeOrderId = existingOrderId || '';
}

function removeTicketItem(index) {
    activeTicketItems.splice(index, 1);
    renderTicketUI();
}

async function sendOrderToKitchen() {
    const newItems = activeTicketItems.filter(i => !i.is_saved);
    if (newItems.length === 0) {
        alert('No hay productos nuevos para enviar.');
        return;
    }

    const takeoutClient = document.getElementById('takeout-client-name').value.trim();
    const payload = {
        table_id: currentActiveTable ? currentActiveTable.id : null,
        client_name: takeoutClient,
        order_type: currentActiveTable ? 'mesa' : 'llevar',
        items: newItems
    };

    try {
        const res = await fetch('api.php?action=send_to_kitchen', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            alert('¡Enviado a cocina y mesa ocupada con éxito!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (e) {
        alert('Error de red.');
    }
}

// KDS
async function loadKDSOrders() {
    try {
        const res = await fetch('api.php?action=get_kds_orders');
        const data = await res.json();
        const container = document.getElementById('kds-orders-container');
        if (!container) return;

        if (!data.success || data.orders.length === 0) {
            container.innerHTML = `<div class="col-span-3 text-center py-12 text-slate-500 font-semibold"><i class="fa-solid fa-circle-check text-2xl mb-2 block text-emerald-500"></i> No hay comensales pendientes en cocina o barra.</div>`;
            return;
        }

        let html = '';
        data.orders.forEach(ord => {
            const timeAgo = Math.floor((new Date() - new Date(ord.created_at)) / 60000);
            const targetLabel = ord.table_number ? ord.table_number : `Llevar: ${ord.client_name || 'Sin Nombre'}`;

            html += `
                <div class="glass-panel p-5 rounded-2xl border border-slate-700 flex flex-col justify-between shadow-xl">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-extrabold text-amber-400 text-lg">${targetLabel}</span>
                            <span class="text-xs bg-slate-800 text-slate-300 px-2.5 py-1 rounded-full"><i class="fa-regular fa-clock mr-1"></i> ${timeAgo} min</span>
                        </div>
                        <div class="flex items-center space-x-2 mb-3 pb-2 border-b border-slate-700/60">
                            <img src="${ord.waiter_photo}" class="w-6 h-6 rounded-full object-cover">
                            <span class="text-xs text-slate-300 font-semibold">Mesero: ${ord.waiter_name}</span>
                        </div>
                        <ul class="text-sm space-y-2 mb-4">
                            ${ord.items.map(i => `
                                <li class="flex justify-between items-center bg-slate-900/60 p-2 rounded-lg border border-slate-800">
                                    <span><strong class="text-amber-400">${i.quantity}x</strong> ${i.product_name}</span>
                                    ${i.item_status === 'listo' 
                                        ? '<span class="text-xs bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-bold">Listo</span>'
                                        : `<button onclick="markItemReady(${i.item_id}, ${ord.order_id})" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-3 py-1 rounded text-xs transition shadow">Marcar Listo</button>`
                                    }
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    } catch (e) {
        console.error("Error al cargar KDS:", e);
    }
}

async function markItemReady(itemId, orderId) {
    try {
        const res = await fetch('api.php?action=update_item_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId, order_id: orderId, status: 'listo' })
        });
        const data = await res.json();
        if (data.success) {
            loadKDSOrders();
        }
    } catch (e) {
        console.error("Error al actualizar ítem:", e);
    }
}

async function checkReadyItemsForWaiters() {
    try {
        const res = await fetch('api.php?action=get_ready_items_for_waiters');
        const data = await res.json();
        const alertContainer = document.getElementById('waiter-alerts-container');
        if (!alertContainer) return;

        if (!data.success || data.ready_items.length === 0) {
            alertContainer.innerHTML = '';
            return;
        }

        let readyAlertsHtml = '';
        data.ready_items.forEach(item => {
            const targetLabel = item.table_number ? `Mesa: ${item.table_number}` : `Para Llevar: ${item.client_name}`;
            readyAlertsHtml += `
                <div class="glass-panel bg-emerald-500/20 border border-emerald-500/50 p-4 rounded-2xl flex justify-between items-center shadow-lg animate-pulse">
                    <div class="flex items-center space-x-3">
                        <div class="bg-emerald-500 text-slate-950 p-2.5 rounded-xl font-bold">
                            <i class="fa-solid fa-bell-concierge text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-emerald-300 text-sm">¡PRODUCTO LISTO EN BARRA / COCINA!</h4>
                            <p class="text-xs text-slate-100 font-semibold mt-0.5">
                                <span class="text-amber-300">${item.quantity}x ${item.product_name}</span> para <span class="text-amber-300">${targetLabel}</span>
                            </p>
                            <p class="text-[11px] text-slate-300 mt-0.5">Mesero: ${item.waiter_name}</p>
                        </div>
                    </div>
                    <button onclick="dismissReadyAlert(${item.item_id})" class="text-xs bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-3 py-2 rounded-xl shadow transition whitespace-nowrap ml-4">
                        <i class="fa-solid fa-check mr-1"></i> Entendido / Recogido
                    </button>
                </div>
            `;
        });

        alertContainer.innerHTML = readyAlertsHtml;
    } catch (e) {
        console.error("Error al verificar ítems listos:", e);
    }
}

async function dismissReadyAlert(itemId) {
    try {
        const res = await fetch('api.php?action=mark_item_delivered', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId })
        });
        const data = await res.json();
        if (data.success) {
            checkReadyItemsForWaiters();
        }
    } catch (e) {
        console.error("Error al descartar alerta:", e);
    }
}

// REPORTES Y ANALÍTICA GERENCIAL CON MANEJO DE ERRORES VISIBLES
async function loadManagerReports() {
    try {
        const res = await fetch('api.php?action=get_manager_reports');
        const data = await res.json();

        if (!data.success) {
            console.error("Error devuelto por la API de reportes:", data.error);
            return;
        }

        // 1. Top Mesero
        if (data.top_waiter) {
            document.getElementById('report-top-waiter-name').innerText = data.top_waiter.name;
            document.getElementById('report-top-waiter-sales').innerText = `$${parseFloat(data.top_waiter.total_sold).toFixed(2)} vendidos (${data.top_waiter.total_orders} órdenes)`;
            if (data.top_waiter.photo) {
                document.getElementById('report-top-waiter-photo').src = data.top_waiter.photo;
            }
        } else {
            document.getElementById('report-top-waiter-name').innerText = "Sin ventas registradas";
            document.getElementById('report-top-waiter-sales').innerText = "$0.00";
        }

        // 2. Gráfica Ventas por Día de la Semana
        const ctxWeekly = document.getElementById('weeklySalesChart');
        if (ctxWeekly) {
            if (weeklySalesChartInstance) {
                weeklySalesChartInstance.destroy();
            }
            weeklySalesChartInstance = new Chart(ctxWeekly, {
                type: 'bar',
                data: {
                    labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
                    datasets: [{
                        label: 'Ventas por Día ($)',
                        data: data.weekly_sales,
                        backgroundColor: 'rgba(251, 191, 36, 0.7)',
                        borderColor: '#fbbf24',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                        x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
                    }
                }
            });
        }

        // 3. Productos más solicitados
        const topProdContainer = document.getElementById('report-top-products-list');
        if (!data.top_products || data.top_products.length === 0) {
            topProdContainer.innerHTML = `<p class="text-xs text-slate-500">Aún no hay productos cobrados.</p>`;
        } else {
            topProdContainer.innerHTML = data.top_products.map((p, idx) => `
                <div class="flex justify-between items-center bg-slate-900/60 p-3 rounded-xl border border-slate-800">
                    <div class="flex items-center space-x-3">
                        <span class="text-xs font-bold bg-amber-500/20 text-amber-400 w-6 h-6 rounded-full flex items-center justify-center">${idx + 1}</span>
                        <div>
                            <h5 class="text-xs font-bold text-slate-200">${p.name}</h5>
                            <span class="text-[11px] text-slate-400">${p.category}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-extrabold text-amber-400">${p.total_qty} vendidos</span>
                        <p class="text-[11px] text-slate-300">$${parseFloat(p.total_revenue).toFixed(2)}</p>
                    </div>
                </div>
            `).join('');
        }

        // 4. Tiempos promedio de preparación
        const prepTimesContainer = document.getElementById('report-prep-times-list');
        if (!data.prep_times || data.prep_times.length === 0) {
            prepTimesContainer.innerHTML = `<p class="text-xs text-slate-500">No hay registros de tiempo de preparación aún.</p>`;
        } else {
            prepTimesContainer.innerHTML = data.prep_times.map(pt => `
                <div class="flex justify-between items-center bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                    <span class="text-xs font-semibold text-slate-200">${pt.name}</span>
                    <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2 py-1 rounded font-bold">
                        <i class="fa-regular fa-clock mr-1"></i> ${pt.avg_prep_minutes} mins promedio
                    </span>
                </div>
            `).join('');
        }

    } catch (e) {
        console.error("Error al cargar reportes gerenciales:", e);
        const topProdContainer = document.getElementById('report-top-products-list');
        if(topProdContainer) topProdContainer.innerHTML = `<p class="text-xs text-rose-400">Error de conexión al cargar reportes.</p>`;
    }
}

// COBRO
async function processCheckout() {
    const container = document.getElementById('ticket-items-container');
    const activeOrderId = container.dataset.activeOrderId;

    if (!activeOrderId) {
        alert('Debes enviar primero el pedido a cocina antes de cobrar.');
        return;
    }

    showTicketMiniaturePreview();
}

function showTicketMiniaturePreview() {
    const targetLabel = currentActiveTable ? currentActiveTable.name : 'Para Llevar';
    const subtotal = document.getElementById('ticket-subtotal').innerText;
    const iva = document.getElementById('ticket-iva').innerText;
    const total = document.getElementById('ticket-total').innerText;

    const modal = document.createElement('div');
    modal.id = 'ticket-modal-preview';
    modal.className = 'fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex justify-center items-center z-50';
    modal.innerHTML = `
        <div class="glass-panel p-6 rounded-2xl border border-slate-700 w-80 text-slate-100 shadow-2xl space-y-4">
            <div class="text-center border-b border-slate-700 pb-3">
                <h4 class="font-extrabold text-amber-400 text-base">VISTA PREVIA DEL TICKET</h4>
                <p class="text-xs text-slate-400">Café Lumière - ${targetLabel}</p>
            </div>
            <div class="bg-white text-slate-900 p-4 rounded-xl font-mono text-xs space-y-2 max-h-60 overflow-y-auto shadow-inner">
                <div class="text-center font-bold">CAFÉ LUMIÈRE POS</div>
                <div class="border-b border-dashed border-slate-400 my-1"></div>
                ${activeTicketItems.map(i => `<div class="flex justify-between"><span>${i.quantity}x ${i.name}</span><span>$${(i.price * i.quantity).toFixed(2)}</span></div>`).join('')}
                <div class="border-b border-dashed border-slate-400 my-1"></div>
                <div class="flex justify-between"><span>Subtotal:</span><span>${subtotal}</span></div>
                <div class="flex justify-between"><span>IVA (16%):</span><span>${iva}</span></div>
                <div class="flex justify-between font-bold text-sm"><span>TOTAL:</span><span>${total}</span></div>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2">
                <button onclick="document.getElementById('ticket-modal-preview').remove()" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 rounded-xl text-xs transition">Cancelar</button>
                <button onclick="confirmAndPrintPayment()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded-xl text-xs transition shadow">Cobrar e Imprimir</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function confirmAndPrintPayment() {
    const container = document.getElementById('ticket-items-container');
    const activeOrderId = container.dataset.activeOrderId;
    const modal = document.getElementById('ticket-modal-preview');
    if(modal) modal.remove();

    try {
        const res = await fetch('api.php?action=process_payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: activeOrderId })
        });
        const data = await res.json();

        if (data.success) {
            printThermalReceipt();
            alert('¡Cobro exitoso! La mesa ha quedado LIBRE.');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (e) {
        alert('Error al procesar el cobro.');
    }
}

function printThermalReceipt() {
    const targetLabel = currentActiveTable ? currentActiveTable.name : 'Para Llevar';
    const subtotal = document.getElementById('ticket-subtotal').innerText;
    const iva = document.getElementById('ticket-iva').innerText;
    const total = document.getElementById('ticket-total').innerText;

    const win = window.open('', 'PrintTicket', 'width=400,height=600');
    win.document.write(`
        <html>
        <head><title>Ticket de Consumo</title>
        <style>body { font-family: monospace; width: 280px; padding: 10px; font-size: 12px; }</style></head>
        <body>
            <div style="text-align:center;"><b>CAFÉ LUMIÈRE</b><br>Mesa/Servicio: ${targetLabel}<br>${new Date().toLocaleString()}</div>
            <hr>
            ${activeTicketItems.map(i => `<div>${i.quantity}x ${i.name} - $${(i.price*i.quantity).toFixed(2)}</div>`).join('')}
            <hr>
            <div>Subtotal: ${subtotal}</div>
            <div>IVA (16%): ${iva}</div>
            <div><b>TOTAL: ${total}</b></div>
            <script>window.onload = function() { window.print(); window.close(); }</script>
        </body></html>
    `);
    win.document.close();
}

// ADMIN
async function loadAdminData() {
    try {
        const res = await fetch('api.php?action=get_admin_data');
        const data = await res.json();

        if (data.success) {
            document.getElementById('admin-waiters-list').innerHTML = data.waiters.map(w => `
                <div class="flex justify-between items-center bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <div class="flex items-center space-x-3">
                        <img src="${w.photo}" class="w-10 h-10 rounded-full object-cover border border-amber-500 shadow">
                        <div>
                            <h5 class="text-sm font-bold text-slate-200">${w.name}</h5>
                            <span class="text-xs text-slate-400">@${w.username}</span>
                        </div>
                    </div>
                    <button onclick="editWaiter(${w.id}, '${w.name}', '${w.username}')" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 px-3 py-1.5 rounded-lg text-xs font-bold transition">Editar</button>
                </div>
            `).join('');

            document.getElementById('admin-products-list').innerHTML = data.products.map(p => `
                <div class="flex justify-between items-center bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                    <div class="flex items-center space-x-3">
                        <img src="${p.image}" class="w-10 h-10 rounded-lg object-cover shadow">
                        <div>
                            <h5 class="text-sm font-bold text-slate-200">${p.name}</h5>
                            <span class="text-xs text-amber-400">$${parseFloat(p.price).toFixed(2)} - ${p.category}</span>
                        </div>
                    </div>
                    <button onclick="editProduct(${p.id}, '${p.name}', '${p.category}', ${p.price})" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 px-3 py-1.5 rounded-lg text-xs font-bold transition">Editar</button>
                </div>
            `).join('');
        }
    } catch (e) {
        console.error("Error al cargar admin:", e);
    }
}

async function addNewWaiter() {
    const name = prompt("Nombre del Mesero:");
    if (!name) return;
    const username = prompt("Usuario de acceso:");
    triggerFileUploadModal("Mesero (" + name + ")", async (formData) => {
        formData.append('name', name);
        formData.append('username', username);
        const res = await fetch('api.php?action=save_waiter', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) { alert('Mesero guardado'); loadAdminData(); } else { alert(data.error); }
    });
}

async function editWaiter(id, currentName, currentUsername) {
    const name = prompt("Modificar Nombre:", currentName);
    if (!name) return;
    const username = prompt("Modificar Usuario:", currentUsername);
    triggerFileUploadModal("Mesero", async (formData) => {
        formData.append('id', id);
        formData.append('name', name);
        formData.append('username', username);
        const res = await fetch('api.php?action=save_waiter', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) { alert('Mesero actualizado'); loadAdminData(); } else { alert(data.error); }
    });
}

async function addNewProduct() {
    const name = prompt("Nombre del Producto:");
    if (!name) return;
    const category = prompt("Categoría:", "Bebidas Calientes");
    const price = parseFloat(prompt("Precio ($):", "50.00"));
    triggerFileUploadModal("Producto (" + name + ")", async (formData) => {
        formData.append('name', name);
        formData.append('category', category);
        formData.append('price', price);
        const res = await fetch('api.php?action=save_product', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) { alert('Producto guardado'); location.reload(); } else { alert(data.error); }
    });
}

async function editProduct(id, currentName, currentCategory, currentPrice) {
    const name = prompt("Modificar Nombre:", currentName);
    if (!name) return;
    const category = prompt("Modificar Categoría:", currentCategory);
    const price = parseFloat(prompt("Modificar Precio ($):", currentPrice));
    triggerFileUploadModal("Producto", async (formData) => {
        formData.append('id', id);
        formData.append('name', name);
        formData.append('category', category);
        formData.append('price', price);
        const res = await fetch('api.php?action=save_product', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) { alert('Producto actualizado'); location.reload(); } else { alert(data.error); }
    });
}

function triggerFileUploadModal(title, callback) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex justify-center items-center z-50';
    modal.innerHTML = `
        <div class="glass-panel p-6 rounded-2xl border border-slate-700 w-96 text-slate-100 shadow-2xl space-y-4">
            <h4 class="font-bold text-amber-400 text-lg">Imagen para ${title}</h4>
            <p class="text-xs text-slate-400">Sube una imagen PNG o JPG:</p>
            <input type="file" id="modal-file-input" accept="image/png, image/jpeg, image/jpg" class="w-full text-xs text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer">
            <div class="flex justify-end space-x-3 pt-2">
                <button onclick="this.closest('.fixed').remove()" class="bg-slate-700 px-4 py-2 rounded-xl text-xs font-bold">Cancelar</button>
                <button id="modal-upload-btn" class="bg-amber-500 text-slate-950 px-4 py-2 rounded-xl text-xs font-bold">Guardar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('modal-upload-btn').onclick = () => {
        const fileInput = document.getElementById('modal-file-input');
        const formData = new FormData();
        if (fileInput.files.length > 0) {
            formData.append('photo_file', fileInput.files[0]);
            formData.append('image_file', fileInput.files[0]);
        }
        modal.remove();
        callback(formData);
    };
}
