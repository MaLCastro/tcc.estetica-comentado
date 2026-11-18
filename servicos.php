<?php

// ============================================================
// 1. INICIAR SESSÃO, CONEXÃO E PROTEÇÃO DA PÁGINA
// ============================================================

// Inicia a sessão do PHP.
// A sessão permite identificar qual usuário está conectado
// e acessar informações armazenadas durante o login.
session_start();


// Carrega o arquivo responsável pela conexão com o banco
// de dados através do PDO.
require_once("conexao.php");


// ------------------------------------------------------------
// VALIDAR O TIPO DE USUÁRIO
// ------------------------------------------------------------

// Verifica se existe um usuário autenticado na sessão.
//
// Também verifica se o tipo do usuário é exatamente "cliente".
// Dessa forma, esta página do catálogo fica restrita às clientes.
if (
    !isset($_SESSION["usuario_id"]) ||
    ($_SESSION["usuario_tipo"] ?? "") !== "cliente"
) {

    // Caso não esteja autenticada como cliente,
    // redireciona para a página de login.
    header("Location: login.php");

    // Interrompe a execução para impedir que o restante
    // da página seja carregado.
    exit();
}


// Recupera o nome da cliente armazenado na sessão.
//
// O operador ?? define "Cliente" como valor padrão
// caso o nome não esteja disponível na sessão.
$nome_cliente = $_SESSION["usuario_nome"] ?? "Cliente";


// ------------------------------------------------------------
// DEFINIR A CATEGORIA SELECIONADA
// ------------------------------------------------------------

// Recupera da URL a categoria escolhida pela cliente.
//
// Exemplos:
//
// servicos.php?categoria=nail
// servicos.php?categoria=lash
// servicos.php?categoria=eyebrow
//
// Caso nenhuma categoria seja informada,
// "nail" será utilizada como padrão.
$categoria = $_GET["categoria"] ?? "nail";


// ------------------------------------------------------------
// MAPEAR OS CÓDIGOS PARA NOMES EXIBIDOS NA INTERFACE
// ------------------------------------------------------------

// O banco utiliza códigos mais curtos para identificar
// as especialidades.
//
// Este array transforma esses códigos em nomes
// apresentados de forma amigável para a cliente.
$categorias = [
    "nail" => "Unhas",
    "lash" => "Cílios",
    "eyebrow" => "Sobrancelhas"
];


// ------------------------------------------------------------
// VALIDAR A CATEGORIA
// ------------------------------------------------------------

// Verifica se a categoria recebida pela URL realmente
// existe dentro do array permitido.
//
// Isso evita enviar valores inesperados para a consulta.
if (!array_key_exists($categoria, $categorias)) {

    // Caso a categoria não exista, utiliza "nail"
    // como categoria padrão.
    $categoria = "nail";
}


// ------------------------------------------------------------
// INICIALIZAR OS DADOS DA PÁGINA
// ------------------------------------------------------------

// Array que armazenará os serviços encontrados
// para a categoria selecionada.
$servicos_categoria = [];


// Inicializa o preço médio da categoria.
// O valor será calculado depois da consulta.
$media_preco = 0;


// Inicializa a quantidade de serviços encontrados.
$quantidade_servicos = 0;


// Indica se ocorreu algum erro durante a consulta.
// Começa como false porque ainda não houve erro.
$erro_servicos = false;


// ============================================================
// 2. BUSCAR SERVIÇOS E PROFISSIONAIS
// ============================================================

// O try permite tratar possíveis erros relacionados
// à consulta ao banco de dados.
try {


    // --------------------------------------------------------
    // MONTAR A CONSULTA SQL
    // --------------------------------------------------------

    // Busca os serviços ativos juntamente com
    // as profissionais responsáveis por eles.
    //
    // A consulta utiliza três tabelas:
    //
    // servicos:
    // contém os dados do procedimento.
    //
    // vinculacao:
    // relaciona o serviço à profissional.
    //
    // usuarios:
    // contém os dados da profissional.
    $sql = "SELECT

        // ID do serviço.
        s.id,

        // Nome do serviço.
        s.nome_servico,

        // Tipo do serviço.
        s.tipo_servico,

        // Descrição do serviço.
        s.descricao,

        // Preço cadastrado para o serviço.
        s.preco,

        // Duração cadastrada para o serviço.
        s.duracao,

        // ID da profissional responsável.
        //
        // O alias permite diferenciar esse ID
        // do ID do próprio serviço.
        u.id AS profissional_id,

        // Nome da profissional responsável.
        u.nome AS profissional_nome,

        // Especialidade cadastrada para a profissional.
        u.especialidade AS profissional_especialidade

    FROM servicos s


    // --------------------------------------------------------
    // RELACIONAR SERVIÇOS E VINCULAÇÕES
    // --------------------------------------------------------

    // A tabela vinculacao informa quais serviços
    // estão associados a cada profissional.
    INNER JOIN vinculacao v
        ON v.servico_id = s.id


    // --------------------------------------------------------
    // RELACIONAR COM OS USUÁRIOS
    // --------------------------------------------------------

    // A tabela usuarios fornece as informações
    // da profissional responsável pelo serviço.
    INNER JOIN usuarios u
        ON u.id = v.profissional_id


    // --------------------------------------------------------
    // FILTROS DA CONSULTA
    // --------------------------------------------------------

    // Somente serviços ativos são apresentados.
    WHERE s.status = 'ativo'

      // Garante que o usuário responsável seja profissional.
      AND u.tipo_usuario = 'profissional'

      // Garante que a profissional também esteja ativa.
      AND u.status = 'ativo'

      // Filtra pela categoria selecionada pela cliente.
      //
      // O valor será enviado posteriormente
      // através do parâmetro :categoria.
      AND u.especialidade = :categoria


    // Organiza primeiro pelo tipo de serviço
    // e depois pelo nome.
    ORDER BY s.tipo_servico ASC, s.nome_servico ASC";


    // --------------------------------------------------------
    // PREPARAR A CONSULTA
    // --------------------------------------------------------

    // Prepara o SQL utilizando PDO.
    //
    // A preparação permite trabalhar com parâmetros
    // de forma segura e ajuda a evitar SQL Injection.
    $stmt = $conexao->prepare($sql);


    // Define o valor do parâmetro :categoria.
    //
    // PDO::PARAM_STR informa que o valor é uma string.
    $stmt->bindValue(
        ":categoria",
        $categoria,
        PDO::PARAM_STR
    );


    // Executa a consulta.
    $stmt->execute();


    // Recupera todos os serviços encontrados.
    //
    // PDO::FETCH_ASSOC retorna cada registro
    // como um array associativo.
    $servicos_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --------------------------------------------------------
    // CALCULAR O PREÇO MÉDIO
    // --------------------------------------------------------

    // Verifica se pelo menos um serviço foi encontrado.
    if (count($servicos_categoria) > 0) {


        // Cria um novo array contendo somente
        // os preços dos serviços.
        //
        // array_map() percorre cada serviço e retorna
        // apenas seu preço convertido para número.
        $precos = array_map(

            // Função executada para cada serviço.
            static fn($servico) => (float) $servico["preco"],

            // Array de serviços que será percorrido.
            $servicos_categoria
        );


        // Soma todos os preços encontrados
        // e divide pela quantidade de serviços.
        //
        // O resultado representa o preço médio
        // da categoria selecionada.
        $media_preco =
            array_sum($precos) /
            count($precos);


        // Guarda a quantidade total de serviços encontrados.
        $quantidade_servicos =
            count($servicos_categoria);
    }


// ------------------------------------------------------------
// TRATAMENTO DE ERRO DA CONSULTA
// ------------------------------------------------------------

} catch (PDOException $erro) {


    // Registra o erro técnico no log do servidor.
    //
    // A mensagem detalhada não é exibida diretamente
    // para a cliente por questões de segurança.
    error_log(
        "Erro ao carregar catálogo de serviços: " .
        $erro->getMessage()
    );


    // Marca a variável como verdadeira.
    //
    // O HTML utilizará essa informação para mostrar
    // uma mensagem amigável de erro.
    $erro_servicos = true;
}


// ============================================================
// 3. FORMATAR A DURAÇÃO DOS SERVIÇOS
// ============================================================

// Cria uma função para transformar a duração,
// armazenada em minutos no banco,
// em um texto mais amigável.
//
// Exemplos:
//
// 30  -> 30 minutos
// 60  -> 1 hora
// 90  -> 1 hora e 30 minutos
// 120 -> 2 horas
function formatarDuracao($minutos)
{

    // Converte o valor recebido para inteiro.
    $minutos = (int) $minutos;


    // Verifica se a duração é inválida.
    //
    // Valores menores ou iguais a zero
    // não representam uma duração válida.
    if ($minutos <= 0) {

        // Retorna uma mensagem padrão.
        return "Não informada";
    }


    // Calcula quantas horas inteiras existem
    // dentro da quantidade de minutos.
    //
    // Exemplo:
    // 150 minutos / 60 = 2 horas.
    $horas = intdiv($minutos, 60);


    // Calcula os minutos restantes depois
    // de retirar as horas completas.
    //
    // Exemplo:
    // 150 % 60 = 30 minutos.
    $resto = $minutos % 60;


    // --------------------------------------------------------
    // DURAÇÕES MENORES QUE UMA HORA
    // --------------------------------------------------------

    // Se não houver nenhuma hora completa,
    // significa que a duração é inferior a 60 minutos.
    if ($horas === 0) {

        // Ajusta "minuto" ou "minutos"
        // dependendo do valor.
        return $minutos .
            ($minutos === 1 ? " minuto" : " minutos");
    }


    // --------------------------------------------------------
    // DURAÇÕES DE HORAS EXATAS
    // --------------------------------------------------------

    // Se não houver minutos restantes,
    // significa que a duração é composta apenas por horas.
    if ($resto === 0) {

        // Ajusta "hora" ou "horas".
        return $horas .
            ($horas === 1 ? " hora" : " horas");
    }


    // --------------------------------------------------------
    // DURAÇÕES COM HORAS E MINUTOS
    // --------------------------------------------------------

    // Caso existam horas e minutos,
    // monta a frase completa.
    return $horas .
        ($horas === 1 ? " hora" : " horas") .
        " e " . $resto . " minutos";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- Define a codificação utilizada pela página. -->
    <meta charset="UTF-8">


    <!--
        Permite que o conteúdo se adapte
        corretamente a celulares e tablets.
    -->
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <!-- Título apresentado na aba do navegador. -->
    <title>Serviços | TCC Estética</title>


    <!--
        Carrega o arquivo CSS externo.
        Assim, o estilo fica separado da estrutura HTML.
    -->
    <link rel="stylesheet" href="style.css">

</head>


<!--
    Classes gerais utilizadas para identificar
    que esta é uma página da área da cliente
    e especificamente do catálogo de serviços.
-->
<body class="pagina-cliente pagina-servicos">


    <!-- ====================================================== -->
    <!-- 4. CABEÇALHO E MENU DA CLIENTE -->
    <!-- ====================================================== -->

    <!--
        Cabeçalho padrão utilizado nas páginas
        da área da cliente.
    -->
    <header class="cabecalho-cliente">


        <!--
            Área destinada à identificação
            visual do sistema.
        -->
        <div class="logo-cliente">

            <!-- Nome apresentado como logo. -->
            <h1>Estética</h1>

        </div>


        <!--
            Menu principal de navegação.

            aria-label identifica o objetivo do menu
            para leitores de tela.
        -->
        <nav
            class="menu-cliente"
            aria-label="Navegação principal"
        >


            <!-- Link para o painel inicial. -->
            <a href="painel_cliente.php">
                Início
            </a>


            <!--
                Link para a página atual.

                A classe "ativo" permite que o CSS
                destaque visualmente a página selecionada.
            -->
            <a
                href="servicos.php"
                class="ativo"
            >
                Serviços
            </a>


            <!-- Link para os agendamentos da cliente. -->
            <a href="meus_agendamentos.php">
                Meus Agendamentos
            </a>


            <!-- Link para o perfil da cliente. -->
            <a href="perfil_cliente.php">
                Meu Perfil
            </a>


            <!--
                Link para encerrar a sessão.

                A classe "menu-sair" permite
                aplicar um estilo diferenciado ao botão.
            -->
            <a
                href="logout.php"
                class="menu-sair"
            >
                Sair
            </a>

        </nav>

    </header>


    <!-- ====================================================== -->
    <!-- 5. CATÁLOGO DE SERVIÇOS -->
    <!-- ====================================================== -->

    <!--
        Área principal do catálogo.

        O layout é dividido em:
        - menu lateral de categorias;
        - conteúdo principal dos serviços.
    -->
    <main class="layout-servicos">


        <!-- ==================================================
             6. MENU LATERAL DE CATEGORIAS
             ================================================== -->

        <!--
            Menu lateral utilizado para selecionar
            a especialidade desejada.
        -->
        <aside class="menu-categorias">


            <!-- Título do menu lateral. -->
            <h2>
                Tipos de serviço
            </h2>


            <!--
                Navegação das categorias.

                aria-label explica a finalidade
                desse segundo menu.
            -->
            <nav aria-label="Categorias de serviços">


                <!--
                    Categoria de unhas.

                    O operador ternário verifica se esta
                    é a categoria atualmente selecionada.

                    Se for "nail", recebe a classe "ativo".
                    Caso contrário, recebe uma string vazia.
                -->
                <a
                    href="servicos.php?categoria=nail"
                    class="<?= $categoria === 'nail' ? 'ativo' : '' ?>"
                >
                    Unhas
                </a>


                <!-- Categoria de cílios. -->
                <a
                    href="servicos.php?categoria=lash"
                    class="<?= $categoria === 'lash' ? 'ativo' : '' ?>"
                >
                    Cílios
                </a>


                <!-- Categoria de sobrancelhas. -->
                <a
                    href="servicos.php?categoria=eyebrow"
                    class="<?= $categoria === 'eyebrow' ? 'ativo' : '' ?>"
                >
                    Sobrancelhas
                </a>

            </nav>

        </aside>


        <!-- ==================================================
             7. CONTEÚDO PRINCIPAL DO CATÁLOGO
             ================================================== -->

        <!--
            Área que apresenta os serviços
            da categoria selecionada.
        -->
        <section class="conteudo-servicos">


            <!-- ==================================================
                 8. INTRODUÇÃO DA CATEGORIA
                 ================================================== -->

            <!--
                Apresentação inicial da categoria selecionada.
            -->
            <div class="intro-servicos">


                <!-- Pequena etiqueta de identificação. -->
                <span class="etiqueta">
                    CATÁLOGO DE PROCEDIMENTOS
                </span>


                <!--
                    Exibe o nome da categoria selecionada.

                    $categorias[$categoria] transforma:
                    nail -> Unhas
                    lash -> Cílios
                    eyebrow -> Sobrancelhas
                -->
                <h2>

                    Serviços de

                    <?= htmlspecialchars(
                        $categorias[$categoria],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </h2>


                <!-- Texto explicativo do catálogo. -->
                <p>

                    Conheça os procedimentos, confira a duração
                    e consulte os valores antes de escolher um horário.

                </p>

            </div>


            <!-- ==================================================
                 9. RESUMO DA CATEGORIA
                 ================================================== -->

            <!--
                O resumo somente aparece quando:
                - não houve erro;
                - existe pelo menos um serviço.
            -->
            <?php if (
                !$erro_servicos &&
                $quantidade_servicos > 0
            ): ?>


                <!--
                    Cartão com informações gerais
                    da categoria selecionada.
                -->
                <div class="resumo-categoria">


                    <!-- Quantidade de procedimentos encontrados. -->
                    <div>

                        <span>
                            Procedimentos disponíveis
                        </span>

                        <strong>
                            <?= $quantidade_servicos ?>
                        </strong>

                    </div>


                    <!-- Preço médio dos serviços. -->
                    <div>

                        <span>
                            Preço médio da categoria
                        </span>

                        <strong>

                            <!--
                                number_format() adapta o número
                                para o formato monetário brasileiro.

                                Exemplo:
                                1500.5 -> 1.500,50
                            -->
                            R$ <?= number_format(
                                $media_preco,
                                2,
                                ",",
                                "."
                            ) ?>

                        </strong>

                    </div>

                </div>


            <?php endif; ?>


            <!-- ==================================================
                 10. TRATAMENTO DE ERRO
                 ================================================== -->

            <!--
                Caso a consulta ao banco tenha falhado,
                apresenta uma mensagem amigável.
            -->
            <?php if ($erro_servicos): ?>


                <!-- Área visual para informar o erro. -->
                <div class="estado-vazio">


                    <!-- Título da mensagem. -->
                    <h3>
                        Não foi possível carregar os serviços
                    </h3>


                    <!-- Orientação para a cliente. -->
                    <p>

                        Tente acessar esta página novamente
                        em alguns instantes.

                    </p>

                </div>


            <!-- ==================================================
                 11. EXIBIR SERVIÇOS ENCONTRADOS
                 ================================================== -->

            <!--
                Se não houve erro, verifica se existem
                serviços para a categoria selecionada.
            -->
            <?php elseif (count($servicos_categoria) > 0): ?>


                <!--
                    Grade que organiza os cartões
                    dos serviços.
                -->
                <div class="grade-servicos-nova">


                    <!--
                        Percorre cada serviço retornado
                        pela consulta ao banco.
                    -->
                    <?php foreach (
                        $servicos_categoria
                        as $servico
                    ): ?>


                        <!--
                            Cartão individual de serviço.
                        -->
                        <article class="cartao-servico-novo">


                            <!-- ==================================================
                                 12. TIPO DO SERVIÇO
                                 ================================================== -->

                            <!--
                                Exibe o tipo do serviço.

                                ucfirst() coloca a primeira letra
                                em maiúscula.

                                Caso o tipo não exista,
                                utiliza "Procedimento".
                            -->
                            <span class="tipo">

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $servico["tipo_servico"] ??
                                        "Procedimento"
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>


                            <!-- ==================================================
                                 13. NOME DO SERVIÇO
                                 ================================================== -->

                            <!--
                                Exibe o nome cadastrado
                                para o procedimento.
                            -->
                            <h3>

                                <?= htmlspecialchars(
                                    $servico["nome_servico"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </h3>


                            <!-- ==================================================
                                 14. DESCRIÇÃO DO SERVIÇO
                                 ================================================== -->

                            <!--
                                Exibe a descrição cadastrada.

                                Caso não exista descrição,
                                apresenta um texto padrão.
                            -->
                            <p>

                                <?= htmlspecialchars(
                                    $servico["descricao"] ?:
                                    "Procedimento realizado com cuidado e atenção.",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </p>


                            <!-- ==================================================
                                 15. DURAÇÃO E PREÇO
                                 ================================================== -->

                            <!--
                                Container que apresenta
                                as informações comerciais do serviço.
                            -->
                            <div class="detalhes-servico">


                                <!-- ------------------------------------------------
                                     DURAÇÃO
                                     ------------------------------------------------ -->

                                <div>

                                    <!-- Nome do campo. -->
                                    <span>
                                        Duração
                                    </span>


                                    <!--
                                        Utiliza a função formatarDuracao()
                                        para transformar os minutos
                                        armazenados no banco em texto.
                                    -->
                                    <strong>

                                        <?= htmlspecialchars(
                                            formatarDuracao(
                                                $servico["duracao"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- ------------------------------------------------
                                     PREÇO
                                     ------------------------------------------------ -->

                                <div>

                                    <!-- Nome do campo. -->
                                    <span>
                                        Preço do serviço
                                    </span>


                                    <!--
                                        Exibe o preço no padrão brasileiro.
                                    -->
                                    <strong>

                                        R$ <?= number_format(
                                            (float) $servico["preco"],
                                            2,
                                            ",",
                                            "."
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- ==================================================
                                 16. PROFISSIONAL RESPONSÁVEL
                                 ================================================== -->

                            <!--
                                Mostra qual profissional
                                realiza o serviço.
                            -->
                            <div class="profissional-servico">


                                <!-- Identifica o campo. -->
                                <strong>
                                    Profissional:
                                </strong>


                                <!--
                                    Exibe o nome da profissional.

                                    htmlspecialchars() protege
                                    o conteúdo antes de exibi-lo.
                                -->
                                <?= htmlspecialchars(
                                    $servico["profissional_nome"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </div>


                            <!-- ==================================================
                                 17. LINK PARA O PERFIL DA PROFISSIONAL
                                 ================================================== -->

                            <!--
                                Permite que a cliente conheça melhor
                                a profissional antes de agendar.

                                O ID da profissional é enviado
                                pela URL para a página
                                perfil_profissional.php.
                            -->
                            <a
                                class="botao-perfil-profissional"
                                href="perfil_profissional.php?id=<?= (int) $servico["profissional_id"] ?>"
                            >

                                Ver perfil da profissional

                            </a>


                            <!-- ==================================================
                                 18. LINK PARA A AGENDA
                                 ================================================== -->

                            <!--
                                Mantém o acesso direto à agenda.

                                São enviados dois IDs:
                                - servico_id;
                                - profissional_id.

                                Com essas informações,
                                agenda.php consegue identificar
                                exatamente qual serviço e profissional
                                foram escolhidos.
                            -->
                            <a
                                class="botao-agenda"
                                href="agenda.php?servico_id=<?= (int) $servico["id"] ?>&profissional_id=<?= (int) $servico["profissional_id"] ?>"
                            >

                                Ver agenda

                            </a>

                        </article>


                    <!--
                        Finaliza o foreach e passa
                        para o próximo serviço.
                    -->
                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <!-- ==================================================
                     19. ESTADO VAZIO
                     ================================================== -->

                <!--
                    Caso a consulta tenha sido executada
                    corretamente, mas nenhum serviço ativo
                    tenha sido encontrado.
                -->
                <div class="estado-vazio">


                    <!-- Título da mensagem. -->
                    <h3>
                        Nenhum procedimento disponível
                    </h3>


                    <!-- Explicação para a cliente. -->
                    <p>

                        Ainda não há serviços ativos cadastrados
                        para esta categoria.

                    </p>

                </div>


            <?php endif; ?>

        </section>

    </main>


    <!-- ====================================================== -->
    <!-- 20. RODAPÉ -->
    <!-- ====================================================== -->

    <!--
        Rodapé da área da cliente.
    -->
    <footer class="rodape-servicos">

        <!-- Identificação do sistema. -->
        <p>
            TCC Estética — Área da Cliente
        </p>

    </footer>

</body>
</html>