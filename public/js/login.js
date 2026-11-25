async function enviar(evento, rota) {
    evento.preventDefault();
    const dados = Object.fromEntries(new FormData(evento.target));

    const resp = await api('POST', rota, dados);
    localStorage.setItem('token', resp.token);
    localStorage.setItem('usuario', JSON.stringify(resp.user));
    location.href = '/pages/vagas.html';
}

document.getElementById('form-login').addEventListener('submit', (e) => enviar(e, '/login'));
document.getElementById('form-cadastro').addEventListener('submit', (e) => enviar(e, '/register'));
