exigirLogin();

const usuario = usuarioLogado();
const recrutador = usuario.tipo === 'recrutador';
const filtro = document.getElementById('filtro');
const formVaga = document.getElementById('form-vaga');

let pagina = 1;
let editandoId = null;
let vagas = [];
let candidatadas = [];

if (recrutador) document.getElementById('area-recrutador').classList.remove('hidden');
else filtro.classList.add('hidden');

async function carregar() {
    if (!recrutador) {
        const minhas = await api('GET', '/candidaturas?page_size=100');
        candidatadas = minhas.data.map((v) => v.id);
    }

    const resp = await api('GET', '/vagas?page=' + pagina + '&congeladas=' + filtro.value);
    vagas = resp.data;

    let html = '';
    for (const v of vagas) {
        html += '<div class="item">'
            + '<h3>' + esc(v.titulo) + ' <span class="suave">(' + esc(v.tipo) + ')</span></h3>'
            + '<p>' + esc(v.descricao) + '</p>'
            + botoes(v)
            + '</div>';
    }
    document.getElementById('lista').innerHTML = html || '<p>Nenhuma vaga.</p>';

    document.getElementById('pagina').textContent = 'Página ' + resp.current_page + ' de ' + resp.last_page;
    document.getElementById('anterior').disabled = resp.current_page <= 1;
    document.getElementById('proxima').disabled = resp.current_page >= resp.last_page;
}

function botoes(v) {
    if (!recrutador) {
        return candidatadas.includes(v.id)
            ? '<button onclick="cancelar(' + v.id + ')">Cancelar candidatura</button>'
            : '<button onclick="candidatar(' + v.id + ')">Candidatar-se</button>';
    }
    return '<button onclick="verCandidatos(' + v.id + ')">Candidatos</button> '
        + '<button onclick="editar(' + v.id + ')">Editar</button> '
        + '<button onclick="congelar(' + v.id + ')">' + (v.ativa ? 'Congelar' : 'Descongelar') + '</button> '
        + '<button onclick="excluir(' + v.id + ')">Excluir</button>';
}

function mudarPagina(delta) {
    pagina += delta;
    carregar();
}

function mudarFiltro() {
    pagina = 1;
    carregar();
}

async function candidatar(id) {
    await api('POST', '/vagas/' + id + '/candidaturas');
    carregar();
}

async function cancelar(id) {
    await api('DELETE', '/vagas/' + id + '/candidaturas');
    carregar();
}

async function congelar(id) {
    await api('PATCH', '/vagas/' + id + '/congelar');
    carregar();
}

async function excluir(id) {
    if (!confirm('Excluir a vaga?')) return;
    await api('DELETE', '/vagas/' + id);
    carregar();
}

async function verCandidatos(id) {
    const resp = await api('GET', '/vagas/' + id + '/candidaturas?page_size=100');

    let html = '<h2>Candidatos</h2>';
    for (const c of resp.data) html += '<p>' + esc(c.nome) + ' - ' + esc(c.email) + '</p>';
    if (!resp.data.length) html += '<p>Ninguém se candidatou ainda.</p>';
    document.getElementById('candidatos').innerHTML = html;
}

function editar(id) {
    const v = vagas.find((vaga) => vaga.id === id);
    formVaga.titulo.value = v.titulo;
    formVaga.descricao.value = v.descricao;
    formVaga.tipo.value = v.tipo;
    editandoId = id;
    document.getElementById('titulo-form').textContent = 'Editar vaga';
}

formVaga.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const dados = Object.fromEntries(new FormData(formVaga));

    if (editandoId) await api('PUT', '/vagas/' + editandoId, dados);
    else await api('POST', '/vagas', dados);

    formVaga.reset();
    editandoId = null;
    document.getElementById('titulo-form').textContent = 'Nova vaga';
    carregar();
});

document.getElementById('btn-sair').addEventListener('click', sair);
carregar();
