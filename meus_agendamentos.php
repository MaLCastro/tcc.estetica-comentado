<!DOCTYPE html>
<!-- Informa ao navegador que estamos utilizando HTML5. -->

<html lang="pt-BR">
<!-- Define o português do Brasil como idioma da página. -->

<head>
    <!-- Início das configurações do documento. -->

    <meta charset="UTF-8">
    <!-- Permite a utilização correta de acentos e caracteres especiais. -->

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Faz o layout se adaptar a diferentes tamanhos de tela. -->

    <title>Meus Agendamentos | TCC Estética</title>
    <!-- Define o título que aparecerá na aba do navegador. -->

    <link rel="stylesheet" href="style.css">
    <!-- Importa o CSS externo utilizado pelo projeto. -->

</head>
<!-- Final das configurações do documento. -->


<body class="pagina-cliente pagina-meus-agendamentos">
<!--
    Corpo da página.

    As classes identificam que esta é uma página da cliente
    e permitem aplicar estilos específicos dos agendamentos.
-->


<!-- ====================================================== -->
<!-- 6. CABEÇALHO E NAVEGAÇÃO DA CLIENTE -->
<!-- ====================================================== -->

<header class="cabecalho-cliente">
    <!-- Cabeçalho principal da área da cliente. -->

    <div class="logo-cliente">
        <!-- Container que apresenta o nome do sistema. -->

        <h1>Estética</h1>
        <!-- Nome exibido no cabeçalho. -->

    </div>
    <!-- Final da logo. -->


    <nav
        class="menu-cliente"
        aria-label="Navegação principal"
    >
        <!--
            Menu principal da cliente.

            aria-label ajuda tecnologias assistivas
            a identificar a finalidade deste menu.
        -->

        <a href="painel_cliente.php">
            Início
        </a>
        <!-- Link para o painel inicial da cliente. -->


        <a href="servicos.php">
            Serviços
        </a>
        <!-- Link para a lista de serviços. -->


        <a
            href="meus_agendamentos.php"
            class="ativo"
            aria-current="page"
        >
            Meus Agendamentos
        </a>
        <!--
            Link da página atual.

            A classe "ativo" permite destacar este item visualmente.

            aria-current informa que este é o item correspondente
            à página atualmente aberta.
        -->


        <a href="perfil_cliente.php">
            Meu Perfil
        </a>
        <!-- Link para o perfil da cliente. -->


        <a
            href="logout.php"
            class="menu-sair"
        >
            Sair
        </a>
        <!-- Link responsável por encerrar a sessão. -->

    </nav>
    <!-- Final da navegação. -->

</header>
<!-- Final do cabeçalho. -->


<!-- ====================================================== -->
<!-- 7. CONTEÚDO PRINCIPAL -->
<!-- ====================================================== -->

<main class="meus-agendamentos-container">
    <!--
        Área principal que contém todos os agendamentos da cliente.
    -->


    <!-- Apresenta o resultado da última operação. -->

    <?php if ($mensagem_agendamento): ?>
        <!--
            Verifica se existe uma mensagem gerada pelo cancelamento.
        -->

        <div
            class="mensagem-agendamento <?= $mensagem_agendamento["tipo"] === "sucesso"
                ? "mensagem-sucesso"
                : "mensagem-erro" ?>"
            role="status"
        >
            <!--
                A classe visual é definida de acordo com o tipo da mensagem.

                Se o tipo for "sucesso", utiliza mensagem-sucesso.
                Caso contrário, utiliza mensagem-erro.
            -->

            <?= escaparTexto($mensagem_agendamento["texto"]) ?>
            <!-- Exibe a mensagem protegendo o conteúdo com htmlspecialchars. -->

        </div>
        <!-- Final da mensagem. -->

    <?php endif; ?>
    <!-- Final da condição da mensagem. -->


    <!-- Apresentação da página. -->

    <section class="agendamentos-introducao">
        <!-- Área introdutória da página. -->

        <span class="agendamentos-etiqueta">
            ÁREA DA CLIENTE
        </span>
        <!-- Pequena identificação da área do sistema. -->


        <h2>Meus Agendamentos</h2>
        <!-- Título principal da página. -->


        <p>
            Olá, <?= escaparTexto($nome_cliente) ?>!
            Consulte seus próximos atendimentos e confira
            o histórico dos serviços realizados.
        </p>
        <!--
            Apresenta uma mensagem personalizada utilizando
            o nome da cliente armazenado na sessão.
        -->

    </section>
    <!-- Final da introdução. -->


    <?php if ($erro_agendamentos): ?>
        <!--
            Se ocorreu um erro na consulta do banco,
            não tenta mostrar os agendamentos.
        -->


        <!-- Apresenta uma mensagem se a consulta falhar. -->

        <div
            class="agendamentos-vazio"
            role="alert"
        >
            <!-- Caixa visual para comunicar o erro. -->

            <h4>
                Não foi possível carregar seus agendamentos
            </h4>
            <!-- Título da mensagem de erro. -->


            <p>
                Ocorreu um problema ao consultar os registros.
                Atualize a página ou tente novamente mais tarde.
            </p>
            <!-- Orientação para a cliente. -->

        </div>
        <!-- Final da mensagem de erro. -->


    <?php else: ?>
        <!--
            Caso não tenha ocorrido erro,
            continua exibindo os agendamentos.
        -->


        <!-- ============================================== -->
        <!-- 8. PRÓXIMOS AGENDAMENTOS -->
        <!-- ============================================== -->

        <section
            class="secao-agendamentos"
            id="proximos-agendamentos"
        >
            <!-- Seção que apresenta os próximos atendimentos. -->


            <div class="agendamentos-titulo-secao">
                <!-- Cabeçalho da seção. -->

                <div>
                    <!-- Agrupa título e descrição. -->

                    <h3>
                        Próximos agendamentos
                    </h3>
                    <!-- Título da seção. -->


                    <p>
                        Confira os detalhes dos seus próximos atendimentos.
                    </p>
                    <!-- Descrição da seção. -->

                </div>
                <!-- Final do bloco de título. -->


                <!-- Contador atualizado conforme os registros encontrados. -->

                <span class="agendamentos-contador">
                    <?= count($proximos_agendamentos) ?>
                </span>
                <!--
                    count() conta quantos próximos agendamentos
                    existem no array.
                -->

            </div>
            <!-- Final do cabeçalho da seção. -->


            <?php if (!empty($proximos_agendamentos)): ?>
                <!-- Verifica se existe pelo menos um próximo agendamento. -->


                <div class="lista-agendamentos">
                    <!-- Container que receberá os cartões. -->


                    <?php foreach ($proximos_agendamentos as $agendamento): ?>
                        <!--
                            Percorre cada próximo agendamento.

                            A variável $agendamento representa
                            o registro atual do loop.
                        -->


                        <!-- Cartão individual do próximo atendimento. -->

                        <article class="cartao-agendamento">
                            <!--
                                Cada agendamento é apresentado
                                como um cartão independente.
                            -->


                            <div class="agendamento-data">
                                <!-- Área da data e horário. -->

                                <span>
                                    Data do atendimento
                                </span>
                                <!-- Identificação da informação. -->


                                <strong>
                                    <?= escaparTexto(
                                        formatarDataAgendamento(
                                            $agendamento["data"]
                                        )
                                    ) ?>
                                </strong>
                                <!--
                                    Converte a data do banco
                                    para o formato brasileiro.
                                -->


                                <span class="agendamento-horario">
                                    <?= escaparTexto(
                                        $agendamento["horario"] ?? "--:--"
                                    ) ?>
                                </span>
                                <!--
                                    Exibe o horário.

                                    Se não existir, apresenta "--:--".
                                -->

                            </div>
                            <!-- Final da área de data. -->


                            <div class="agendamento-detalhes">
                                <!-- Área com os detalhes do serviço. -->


                                <h4>
                                    <?= escaparTexto(
                                        $agendamento["servico"] ?? "Serviço"
                                    ) ?>
                                </h4>
                                <!--
                                    Exibe o nome do serviço.
                                -->


                                <p>
                                    <strong>Profissional:</strong>

                                    <?= escaparTexto(
                                        $agendamento["profissional"] ??
                                        "Não informada"
                                    ) ?>
                                </p>
                                <!-- Exibe o nome da profissional. -->


                                <p>
                                    <strong>Valor:</strong>
                                    <!-- Identifica o preço. -->


                                    <?php if ($agendamento["preco"] !== null): ?>
                                        <!--
                                            Verifica se existe um preço.
                                        -->

                                        R$
                                        <!-- Símbolo da moeda brasileira. -->


                                        <?= number_format(
                                            (float) $agendamento["preco"],
                                            2,
                                            ",",
                                            "."
                                        ) ?>
                                        <!--
                                            Converte o valor para o formato brasileiro.

                                            Exemplo:
                                            1500.50 -> 1.500,50
                                        -->

                                    <?php else: ?>
                                        <!-- Caso o preço seja NULL. -->

                                        A confirmar
                                        <!-- Indica que ainda não existe valor definido. -->

                                    <?php endif; ?>
                                    <!-- Final da condição do preço. -->

                                </p>
                                <!-- Final do valor. -->


                                <!-- ================================================== -->
                                <!-- DADOS PIX DA PROFISSIONAL -->
                                <!-- ================================================== -->

                                <?php if (
                                    !empty(
                                        trim(
                                            (string) (
                                                $agendamento["chave_pix"] ?? ""
                                            )
                                        )
                                    )
                                ): ?>
                                    <!--
                                        Verifica se a profissional possui
                                        uma chave Pix cadastrada.

                                        trim() remove espaços.
                                        empty() verifica se existe conteúdo.
                                    -->


                                    <div class="agendamento-pix">
                                        <!-- Área específica dos dados Pix. -->


                                        <p>
                                            <strong>
                                                Pagamento via Pix
                                            </strong>
                                        </p>
                                        <!-- Identifica a forma de pagamento. -->


                                        <p>
                                            <strong>
                                                Tipo de chave:
                                            </strong>

                                            <?= escaparTexto(
                                                !empty(
                                                    $agendamento["tipo_chave_pix"]
                                                )
                                                    ? $agendamento["tipo_chave_pix"]
                                                    : "Não informado"
                                            ) ?>
                                        </p>
                                        <!--
                                            Mostra o tipo da chave Pix.

                                            Caso não esteja informado,
                                            apresenta "Não informado".
                                        -->


                                        <p>
                                            <strong>
                                                Chave Pix:
                                            </strong>

                                            <span class="chave-pix">
                                                <?= escaparTexto(
                                                    $agendamento["chave_pix"]
                                                ) ?>
                                            </span>
                                        </p>
                                        <!-- Exibe a chave Pix cadastrada. -->

                                    </div>
                                    <!-- Final da área Pix. -->


                                <?php endif; ?>
                                <!-- Final da verificação da chave Pix. -->


                                <!-- Exibe o status atual do atendimento. -->

                                <?php
                                // Chama a função que transforma o status
                                // em um texto e uma classe CSS.
                                $status = apresentarStatus(
                                    $agendamento["status"] ?? ""
                                );
                                ?>
                                <!--
                                    Depois dessa execução:
                                    $status["texto"] contém o texto do status.
                                    $status["classe"] contém a classe CSS.
                                -->


                                <span
                                    class="agendamento-status <?= escaparTexto(
                                        $status["classe"]
                                    ) ?>"
                                >
                                    <?= escaparTexto(
                                        $status["texto"]
                                    ) ?>
                                </span>
                                <!--
                                    Exibe visualmente o status do atendimento.
                                -->

                            </div>
                            <!-- Final dos detalhes. -->


                            <!-- Mostra o botão somente para status canceláveis. -->

                            <?php
                            // Recupera o status atual para decidir
                            // se o botão de cancelamento será exibido.
                            $status_botao = strtolower(
                                trim(
                                    (string) (
                                        $agendamento["status"] ?? ""
                                    )
                                )
                            );
                            ?>
                            <!--
                                O status é normalizado para comparação.
                            -->


                            <?php if (in_array(
                                $status_botao,
                                ["pendente", "confirmado", "alterado"],
                                true
                            )): ?>
                                <!--
                                    O botão só aparece quando o status
                                    permite cancelamento.
                                -->


                                <div class="agendamento-acoes">
                                    <!-- Área das ações do agendamento. -->


                                    <!--
                                        Encaminha para a tela especial
                                        de confirmação do cancelamento.

                                        O ID identifica exatamente
                                        qual agendamento será cancelado.
                                    -->

                                    <a
                                        href="confirmar_cancelamento.php?id=<?= (int) $agendamento["id"] ?>"
                                        class="btn-cancelar-agendamento"
                                    >
                                        Cancelar agendamento
                                    </a>
                                    <!--
                                        Link que leva à página de confirmação.
                                    -->

                                </div>
                                <!-- Final das ações. -->


                            <?php endif; ?>
                            <!-- Final da condição dos status canceláveis. -->


                        </article>
                        <!-- Final do cartão do agendamento. -->


                    <?php endforeach; ?>
                    <!-- Final do loop dos próximos agendamentos. -->


                </div>
                <!-- Final da lista de agendamentos. -->


            <?php else: ?>
                <!--
                    Executado quando não existe nenhum próximo atendimento.
                -->


                <!-- Mensagem apresentada quando não há próximos atendimentos. -->

                <div class="agendamentos-vazio">
                    <!-- Caixa de estado vazio. -->


                    <h4>
                        Nenhum próximo agendamento
                    </h4>
                    <!-- Título da mensagem. -->


                    <p>
                        Quando você realizar um agendamento,
                        os detalhes aparecerão nesta seção.
                    </p>
                    <!-- Explicação para a cliente. -->


                    <a
                        href="servicos.php"
                        class="agendamentos-botao"
                    >
                        Conhecer serviços
                    </a>
                    <!--
                        Link que leva a cliente para conhecer
                        os serviços disponíveis.
                    -->

                </div>
                <!-- Final da mensagem de estado vazio. -->


            <?php endif; ?>
            <!-- Final da verificação dos próximos agendamentos. -->


        </section>
        <!-- Final da seção de próximos agendamentos. -->


        <!-- ============================================== -->
        <!-- 9. HISTÓRICO DE ATENDIMENTOS -->
        <!-- ============================================== -->

        <section
            class="secao-agendamentos"
            id="historico-agendamentos"
        >
            <!--
                Seção responsável por apresentar os agendamentos
                que já foram concluídos, cancelados ou que ficaram
                no passado.
            -->


            <div class="agendamentos-titulo-secao">
                <!-- Cabeçalho da seção histórica. -->

                <div>
                    <!-- Agrupa título e descrição. -->

                    <h3>
                        Histórico de atendimentos
                    </h3>
                    <!-- Título da seção. -->


                    <p>
                        Consulte os atendimentos anteriores e seus status.
                    </p>
                    <!-- Descrição da seção. -->

                </div>
                <!-- Final do bloco de título. -->


                <!-- Contador dos registros do histórico. -->

                <span class="agendamentos-contador">
                    <?= count($historico_agendamentos) ?>
                </span>
                <!--
                    count() informa quantos registros existem
                    no histórico.
                -->

            </div>
            <!-- Final do cabeçalho. -->


            <?php if (!empty($historico_agendamentos)): ?>
                <!-- Verifica se existe algum registro histórico. -->


                <div class="lista-agendamentos">
                    <!-- Container dos cartões históricos. -->


                    <?php foreach ($historico_agendamentos as $agendamento): ?>
                        <!--
                            Percorre cada registro do histórico.
                        -->


                        <!-- Cartão individual do histórico. -->

                        <article class="cartao-agendamento cartao-historico">
                            <!--
                                O cartão possui duas classes:

                                cartao-agendamento:
                                estilo geral do agendamento.

                                cartao-historico:
                                permite aplicar estilos específicos
                                para registros históricos.
                            -->


                            <div class="agendamento-data">
                                <!-- Área da data. -->

                                <span>
                                    Data do atendimento
                                </span>
                                <!-- Identificação da data. -->


                                <strong>
                                    <?= escaparTexto(
                                        formatarDataAgendamento(
                                            $agendamento["data"]
                                        )
                                    ) ?>
                                </strong>
                                <!--
                                    Exibe a data convertida para o formato brasileiro.
                                -->


                                <span class="agendamento-horario">
                                    <?= escaparTexto(
                                        $agendamento["horario"] ?? "--:--"
                                    ) ?>
                                </span>
                                <!-- Exibe o horário do atendimento. -->

                            </div>
                            <!-- Final da data. -->


                            <div class="agendamento-detalhes">
                                <!-- Detalhes do atendimento. -->


                                <h4>
                                    <?= escaparTexto(
                                        $agendamento["servico"] ?? "Serviço"
                                    ) ?>
                                </h4>
                                <!-- Exibe o serviço realizado. -->


                                <p>
                                    <strong>Profissional:</strong>

                                    <?= escaparTexto(
                                        $agendamento["profissional"] ??
                                        "Não informada"
                                    ) ?>
                                </p>
                                <!-- Exibe o nome da profissional. -->


                                <p>
                                    <strong>Valor:</strong>
                                    <!-- Identifica o valor do atendimento. -->


                                    <?php if ($agendamento["preco"] !== null): ?>
                                        <!-- Verifica se existe preço. -->

                                        R$
                                        <!-- Símbolo da moeda brasileira. -->


                                        <?= number_format(
                                            (float) $agendamento["preco"],
                                            2,
                                            ",",
                                            "."
                                        ) ?>
                                        <!--
                                            Formata o preço com duas casas decimais
                                            e padrão brasileiro.
                                        -->


                                    <?php else: ?>
                                        <!-- Caso o preço seja nulo. -->

                                        A confirmar
                                        <!-- Informa que o preço ainda não foi definido. -->

                                    <?php endif; ?>
                                    <!-- Final da condição do preço. -->

                                </p>
                                <!-- Final do preço. -->


                                <!-- ================================================== -->
                                <!-- DADOS PIX DA PROFISSIONAL -->
                                <!-- ================================================== -->

                                <?php if (!empty($agendamento["chave_pix"])): ?>
                                    <!--
                                        Verifica se existe uma chave Pix cadastrada
                                        para a profissional.
                                    -->


                                    <div class="agendamento-pix">
                                        <!-- Área das informações Pix. -->


                                        <p>
                                            <strong>
                                                Pagamento via Pix
                                            </strong>
                                        </p>
                                        <!-- Identificação da forma de pagamento. -->


                                        <p>
                                            <strong>
                                                Tipo de chave:
                                            </strong>

                                            <?= escaparTexto(
                                                !empty(
                                                    $agendamento["tipo_chave_pix"]
                                                )
                                                    ? $agendamento["tipo_chave_pix"]
                                                    : "Não informado"
                                            ) ?>
                                        </p>
                                        <!--
                                            Mostra o tipo da chave Pix.
                                        -->


                                        <p>
                                            <strong>
                                                Chave Pix:
                                            </strong>

                                            <span class="chave-pix">
                                                <?= escaparTexto(
                                                    $agendamento["chave_pix"]
                                                ) ?>
                                            </span>
                                        </p>
                                        <!--
                                            Mostra a chave Pix da profissional.
                                        -->

                                    </div>
                                    <!-- Final dos dados Pix. -->


                                <?php endif; ?>
                                <!-- Final da verificação da chave Pix. -->


                                <!-- Exibe o status atual do atendimento. -->

                                <?php
                                // Converte o status interno
                                // para o texto e classe CSS correspondentes.
                                $status = apresentarStatus(
                                    $agendamento["status"] ?? ""
                                );
                                ?>
                                <!--
                                    $status["texto"] contém o nome amigável.
                                    $status["classe"] contém a classe CSS.
                                -->


                                <span
                                    class="agendamento-status <?= escaparTexto(
                                        $status["classe"]
                                    ) ?>"
                                >
                                    <?= escaparTexto(
                                        $status["texto"]
                                    ) ?>
                                </span>
                                <!-- Exibe o status do atendimento. -->

                            </div>
                            <!-- Final dos detalhes. -->


                        </article>
                        <!-- Final do cartão histórico. -->


                    <?php endforeach; ?>
                    <!-- Final do loop do histórico. -->


                </div>
                <!-- Final da lista histórica. -->


            <?php else: ?>
                <!--
                    Executado quando a cliente ainda não possui
                    nenhum registro no histórico.
                -->


                <!-- Mensagem apresentada quando o histórico está vazio. -->

                <div class="agendamentos-vazio">
                    <!-- Caixa de estado vazio. -->


                    <h4>
                        Nenhum registro no histórico
                    </h4>
                    <!-- Título do estado vazio. -->


                    <p>
                        Seus atendimentos anteriores aparecerão aqui
                        quando houver registros correspondentes.
                    </p>
                    <!-- Explicação para a cliente. -->

                </div>
                <!-- Final do estado vazio. -->


            <?php endif; ?>
            <!-- Final da verificação do histórico. -->


        </section>
        <!-- Final da seção de histórico. -->


    <?php endif; ?>
    <!-- Final da condição de erro dos agendamentos. -->


</main>
<!-- Final do conteúdo principal. -->


<!-- Rodapé discreto da área da cliente. -->

<footer class="rodape-agendamentos">
    <!-- Rodapé da página. -->

    <p>
        TCC Estética — Área da Cliente
    </p>
    <!-- Identificação do sistema. -->

</footer>
<!-- Final do rodapé. -->


</body>
<!-- Final do corpo da página. -->


</html>
<!-- Final do documento HTML. -->