exigirLogin();

const usuario = usuarioLogado();
const form = document.getElementById('form-perfil');

async function carregarPerfil() {
    const eu = await api('GET', '/me');
    localStorage.setItem('usuario', JSON.stringify(eu));
    form.nome.value = eu.nome;
    form.email.value = eu.email;
    form.tipo.value = eu.tipo;
}

async function carregarCandidaturas() {
    const resp = await api('GET', '/candidaturas?page_size=100');

    let html = '';
    for (const v of resp.data) {
        html += '<div class="item">'
            + '<strong>' + esc(v.titulo) + '</strong> '
            + '<span class="suave">(' + esc(v.tipo) + ') - ' + esc(v.pivot.status) + '</span> '
            + '<button onclick="cancelar(' + v.id + ')">Cancelar</button>'
            + '</div>';
    }
    document.getElementById('lista').innerHTML = html || '<p>Nenhuma candidatura.</p>';
}

async function cancelar(id) {
    await api('DELETE', '/vagas/' + id + '/candidaturas');
    carregarCandidaturas();
}

form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    await api('PUT', '/users/' + usuario.id, Object.fromEntries(new FormData(form)));
    mostrar('Perfil atualizado.', 'ok');
    carregarPerfil();
});

document.getElementById('btn-excluir').addEventListener('click', async () => {
    if (!confirm('Excluir sua conta?')) return;
    await api('DELETE', '/users/' + usuario.id);
    localStorage.clear();
    location.href = '/pages/login.html';
});

document.getElementById('btn-sair').addEventListener('click', sair);

carregarPerfil();
if (usuario.tipo === 'candidato') carregarCandidaturas();
else document.getElementById('area-candidaturas').classList.add('hidden');
