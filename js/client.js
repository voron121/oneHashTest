/**
 *
 * @param data
 */
function renderData(data) {
    let blocksTable = document.getElementById('blocks');
    let transactionsTable = document.getElementById('transactions');
    let blocks = "";
    let transactions = "";

    for (let i = 0; i < data.blocks.length; i++) { // выведет 0, затем 1, затем 2
        blocks += "<tr><th>"+data.blocks[i].id+"</th><th>"+data.blocks[i].block+"</th></tr>";
    }
    for (let i = 0; i < data.hashs.length; i++) { // выведет 0, затем 1, затем 2
        transactions += "<tr><th>"+data.hashs[i].blockId+"</th><th>"+data.hashs[i].transactionHash+"</th></tr>";
    }

    blocksTable.innerHTML = blocks;
    transactionsTable.innerHTML = transactions;
}

let socket = new WebSocket('ws://localhost:8000');
socket.onopen = function() {
    document.getElementById("connectStatus").innerHTML = '<span class="badge badge-success">Соединение установлено</span>';
};
socket.onclose = function(event) {
    if (event.wasClean) {
        document.getElementById("connectStatus").innerHTML = '<span class="badge badge-info">Соединение закрыто</span>';
    } else {
        document.getElementById("connectStatus").innerHTML = '<span class="badge badge-warning">Соединение закрыто с ошибкой</span>';
    }
    console.log(event);
};
socket.onmessage = function(event) {
    renderData(JSON.parse(event.data));
};
socket.onerror = function(error) {
    console.log("Ошибка " + error);
};