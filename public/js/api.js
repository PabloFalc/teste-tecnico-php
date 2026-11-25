async function api(metodo, caminho, corpo) {
    const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
    const token = localStorage.getItem('token');
    if (token) headers.Authorization = 'Bearer ' + token;

    const resp = await fetch('/api' + caminho, {
        method: metodo,
        headers: headers,
        body: corpo ? JSON.stringify(corpo) : undefined,
    });
    const dados = resp.status === 204 ? null : await resp.json();

    if (!resp.ok) {
        throw new Error(dados.errors ? Object.values(dados.errors).flat().join(' ') : dados.message);
    }
    return dados;
}

function esc(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function usuarioLogado() {
    return JSON.parse(localStorage.getItem('usuario'));
}

function exigirLogin() {
    if (!localStorage.getItem('token')) location.href = '/pages/login.html';
}

async function sair() {
    await api('POST', '/logout');
    localStorage.clear();
    location.href = '/pages/login.html';
}

function mostrar(texto, classe) {
    document.getElementById('mensagem').innerHTML = '<p class="' + classe + '">' + esc(texto) + '</p>';
}

window.addEventListener('unhandledrejection', (evento) => mostrar(evento.reason.message, 'erro'));
