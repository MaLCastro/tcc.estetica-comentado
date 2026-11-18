<?php

// ============================================================
// INICIAR SESSÃO, CONEXÃO E VALIDAR ACESSO
// ============================================================

// Inicia a sessão do PHP.
// A sessão permite acessar informações armazenadas no servidor,
// como o ID e o tipo de usuário que está atualmente conectado.
session_start();

// Inclui o arquivo responsável pela conexão com o banco de dados.
// O require_once garante que o arquivo seja incluído apenas uma vez.
require_once("conexao.php");


// Permite o acesso somente para clientes autenticadas.
// Primeiro verifica se existe um usuário conectado.
// Depois verifica se o tipo de usuário é exatamente "cliente".
// O operador ?? "" evita erro caso "usuario_tipo" não exista na sessão.
if (
    !isset($_SESSION["usuario_id"]) ||
    ($_SESSION["usuario_tipo"] ?? "") !== "cliente"
) {

    // Se o usuário não estiver autenticado como cliente,
// redireciona para a página de login.
    header("Location: login.php");

    // Encerra imediatamente a execução do arquivo.
// Isso impede que o restante da página seja executado após o redirecionamento.
    exit();
}


// ============================================================
// RECEBER OS DADOS DA URL
// ============================================================

// Recebe o ID do serviço enviado pela página anterior.
// FILTER_VALIDATE_INT garante que o valor seja validado como número inteiro.
$servico_id = filter_input(
    INPUT_GET,
    "servico_id",
    FILTER_VALIDATE_INT
);

// Recebe o ID da profissional responsável pelo serviço.
// Assim como o serviço, o valor é validado como inteiro.
$profissional_id = filter_input(
    INPUT_GET,
    "profissional_id",
    FILTER_VALIDATE_INT
);

// Recebe a data enviada pela URL.
// Caso nenhuma data seja informada, utiliza automaticamente a data atual.
$data_recebida = $_GET["data"] ?? date("Y-m-d");


// Define o fuso horário utilizado pelo sistema.
// America/Sao_Paulo corresponde ao horário oficial utilizado no projeto.
date_default_timezone_set("America/Sao_Paulo");

// Guarda a data atual no formato utilizado pelo banco de dados.
// O formato Y-m-d corresponde a ano-mês-dia.
$data_hoje = date("Y-m-d");

// Variável utilizada para armazenar mensagens de erro.
// Começa vazia porque inicialmente não existe nenhum erro.
$erro = "";

// Variável que posteriormente receberá os dados do serviço.
// Começa como null porque o serviço ainda não foi consultado.
$servico = null;

// Variável que posteriormente armazenará o nome da profissional.
$profissional = null;

// Array que armazenará os horários disponíveis para o dia escolhido.
$horarios_dia = [];

// Armazena uma mensagem relacionada à disponibilidade da data.
// Exemplos: data não liberada, profissional não atende ou sem horários.
$status_dia = "";


// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

// Esta função protege os textos que serão exibidos no HTML.
// htmlspecialchars transforma caracteres especiais em entidades HTML,
// evitando que conteúdos vindos do banco ou da URL sejam interpretados
// como código HTML ou JavaScript pelo navegador.
function escapar($valor)
{
    // Converte o valor para texto e aplica a proteção HTML.
// ENT_QUOTES também protege aspas simples e duplas.
// UTF-8 define a codificação utilizada pelo projeto.
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ------------------------------------------------------------
// CONVERTER DURAÇÃO PARA MINUTOS
// ------------------------------------------------------------

// Esta função transforma diferentes formatos de duração
// em uma quantidade única de minutos.
// Isso é necessário porque o sistema precisa fazer cálculos
// com os horários dos serviços.
function converterDuracaoMinutos($duracao)
{
    // Remove espaços extras do início e do final do valor.
// Também garante que o valor seja tratado como texto.
    $texto = trim((string)$duracao);


    // Verifica se o valor contém somente números.
// Se for, considera que o valor já representa minutos.
    if (ctype_digit($texto)) {

        // Converte o texto numérico para inteiro e retorna o resultado.
        return (int)$texto;
    }


    // Verifica se a duração está no formato de horas,
// como "2h", "1h30" ou "2h 30min".
    if (
        preg_match(
            '/^(\d+)\s*h(?:\s*(\d+)\s*(?:min)?)?$/u',
            mb_strtolower($texto),
            $partes
        )
    ) {

        // Converte as horas para minutos.
// Depois soma os minutos adicionais, caso existam.
        return ((int)$partes[1] * 60)
            + (isset($partes[2]) ? (int)$partes[2] : 0);
    }


    // Verifica se a duração está no formato "30min", "45min" etc.
    if (
        preg_match(
            '/^(\d+)\s*min$/u',
            mb_strtolower($texto),
            $partes
        )
    ) {

        // Retorna diretamente a quantidade de minutos encontrada.
        return (int)$partes[1];
    }


    // Verifica se a duração está no formato "02:30",
// interpretando o primeiro valor como horas e o segundo como minutos.
    if (preg_match('/^(\d+):(\d+)$/', $texto, $partes)) {

        // Converte as horas para minutos e soma os minutos.
        return ((int)$partes[1] * 60)
            + (int)$partes[2];
    }


    // Se nenhum dos formatos esperados for encontrado,
// retorna zero para indicar que a duração não pôde ser interpretada.
    return 0;
}


// ------------------------------------------------------------
// FORMATAR DURAÇÃO PARA EXIBIÇÃO
// ------------------------------------------------------------

// Converte uma quantidade de minutos para um formato
// mais amigável para a cliente.
// Exemplos: 120 -> "2h"; 90 -> "1h30min".
function formatarDuracaoAmigavel($minutos)
{
    // intdiv calcula quantas horas completas existem nos minutos.
    $horas = intdiv((int)$minutos, 60);

    // O operador % encontra os minutos que sobraram
// depois de retirar as horas completas.
    $minutos_restantes = (int)$minutos % 60;


    // Se houver horas e minutos restantes,
// apresenta os dois valores.
    if ($horas > 0 && $minutos_restantes > 0) {
        return "{$horas}h{$minutos_restantes}min";
    }


    // Se houver somente horas,
// apresenta apenas a quantidade de horas.
    if ($horas > 0) {
        return "{$horas}h";
    }


    // Caso tenha somente minutos,
// apresenta somente a quantidade de minutos.
    return "{$minutos_restantes}min";
}


// ------------------------------------------------------------
// MONTAR URL DA AGENDA
// ------------------------------------------------------------

// Cria os links utilizados para navegar entre datas.
// Mantém o serviço, a profissional e a data selecionada na URL.
function urlAgenda($servico_id, $profissional_id, $data)
{
    // http_build_query transforma os dados do array
// em parâmetros de URL.
// Exemplo: servico_id=5&profissional_id=2&data=2026-11-18
    return "agenda.php?" . http_build_query([
        "servico_id" => $servico_id,
        "profissional_id" => $profissional_id,
        "data" => $data
    ]);
}


// ------------------------------------------------------------
// VALIDAR DATA
// ------------------------------------------------------------

// Verifica se uma data recebida pela URL realmente existe.
// Isso evita trabalhar com datas em formatos incorretos.
function dataValida($valor)
{
    // Primeiro verifica se o valor é texto e segue exatamente
// o padrão ano-mês-dia.
    if (
        !is_string($valor) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)
    ) {
        return false;
    }


    // Cria um objeto DateTime utilizando o formato esperado.
// O "!" faz com que os outros componentes da data sejam zerados.
    $data = DateTime::createFromFormat("!Y-m-d", $valor);


    // Confirma que a data foi criada corretamente
// e que o formato retornado é exatamente igual ao recebido.
    return $data && $data->format("Y-m-d") === $valor;
}


// ============================================================
// VALIDAR A DATA E DEFINIR O MÊS DO CALENDÁRIO
// ============================================================

// Caso a data recebida pela URL seja inválida,
// o sistema utiliza a data atual como padrão.
if (!dataValida($data_recebida)) {
    $data_recebida = $data_hoje;
}


// Não permite que a cliente selecione uma data anterior ao dia atual.
// Se uma data passada for enviada pela URL,
// automaticamente utiliza a data de hoje.
$data_selecionada =
    ($data_recebida < $data_hoje)
        ? $data_hoje
        : $data_recebida;


// Cria um objeto DateTime com a data que será exibida.
$data_obj_selecionada = new DateTime($data_selecionada);


// Cria um objeto representando o primeiro dia do mês selecionado.
// Isso facilita a montagem do calendário.
$mes_obj = new DateTime(
    $data_obj_selecionada->format("Y-m-01")
);


// Cria uma cópia do objeto do mês e volta um mês.
// O clone evita alterar o objeto original.
$mes_anterior_obj =
    (clone $mes_obj)->modify("-1 month");


// Cria uma cópia do objeto do mês e avança um mês.
$mes_seguinte_obj =
    (clone $mes_obj)->modify("+1 month");


// Obtém o número do mês.
// Exemplo: janeiro = 1, fevereiro = 2.
$mes = (int)$mes_obj->format("m");


// Obtém o ano do calendário.
$ano = (int)$mes_obj->format("Y");


// Array utilizado para mostrar o nome do mês em português.
$nome_mes = [
    1 => "Janeiro",
    2 => "Fevereiro",
    3 => "Março",
    4 => "Abril",
    5 => "Maio",
    6 => "Junho",
    7 => "Julho",
    8 => "Agosto",
    9 => "Setembro",
    10 => "Outubro",
    11 => "Novembro",
    12 => "Dezembro"
];


// ============================================================
// CARREGAR O SERVIÇO E OS DADOS DA PROFISSIONAL
// ============================================================

// Verifica se os IDs necessários foram realmente recebidos.
// Sem o serviço e a profissional não é possível montar a agenda.
if (!$servico_id || !$profissional_id) {

    // Informa o problema para ser exibido posteriormente na página.
    $erro = "Serviço ou profissional não informado.";

} else {

    // Inicia um bloco de tratamento de erros.
// Assim, problemas de banco de dados não interrompem
// a página mostrando mensagens técnicas para a cliente.
    try {

        // Prepara a consulta SQL utilizando parâmetros nomeados.
// A vinculação é utilizada para confirmar que o serviço
// realmente está disponível para aquela profissional.
        $consulta = $conexao->prepare("
            SELECT
                s.id,
                s.nome_servico,
                s.duracao,
                s.preco,
                v.duracao_customizada,
                v.preco_customizado,
                u.nome AS profissional_nome
            FROM servicos s
            INNER JOIN vinculacao v
                ON v.servico_id = s.id
               AND v.profissional_id = :pid
            INNER JOIN usuarios u
                ON u.id = v.profissional_id
            WHERE s.id = :sid
              AND s.status = 'ativo'
              AND u.status = 'ativo'
            LIMIT 1
        ");


        // Substitui os parâmetros da consulta pelos IDs recebidos.
// Isso evita concatenar diretamente valores externos dentro do SQL.
        $consulta->execute([
            ":sid" => $servico_id,
            ":pid" => $profissional_id
        ]);


        // Recupera o primeiro resultado encontrado como array associativo.
// Se nenhum registro for encontrado, o resultado será false.
        $servico = $consulta->fetch(PDO::FETCH_ASSOC);


        // Verifica se o serviço realmente foi encontrado.
        if (!$servico) {

            // Informa que o serviço não está disponível para aquela profissional.
            $erro = "Serviço indisponível para esta profissional.";

        } else {

            // Se existir uma duração personalizada na tabela vinculacao,
// utiliza essa duração.
// Caso contrário, utiliza a duração padrão cadastrada no serviço.
            $duracao_texto =
                !empty($servico["duracao_customizada"])
                    ? $servico["duracao_customizada"]
                    : $servico["duracao"];


            // Converte a duração para minutos,
// pois os cálculos dos horários serão feitos em minutos.
            $duracao_servico =
                converterDuracaoMinutos($duracao_texto);


            // Cria uma versão amigável da duração
// para ser mostrada na interface da cliente.
            $duracao_exibicao =
                formatarDuracaoAmigavel($duracao_servico);


            // Se existir preço personalizado,
// utiliza o preço definido para aquela profissional.
// Caso contrário, utiliza o preço padrão do serviço.
            $preco_final =
                ($servico["preco_customizado"] !== null &&
                 $servico["preco_customizado"] !== "")
                    ? (float)$servico["preco_customizado"]
                    : (float)$servico["preco"];


            // Guarda o nome da profissional em uma variável
// para facilitar sua utilização no HTML.
            $profissional = $servico["profissional_nome"];


            // Verifica se a duração encontrada é válida.
// Uma duração igual ou menor que zero impediria
// o cálculo correto dos horários.
            if ($duracao_servico <= 0) {
                $erro =
                    "A duração deste serviço precisa ser conferida pela profissional.";
            }
        }

    } catch (Throwable $e) {

        // Registra o erro técnico no log do servidor.
// A mensagem detalhada não é exibida para a cliente.
        error_log(
            "Erro ao carregar serviço na agenda: "
            . $e->getMessage()
        );

        // Mostra uma mensagem amigável para a cliente.
        $erro =
            "Não foi possível carregar os dados do serviço. Tente novamente.";
    }
}


// ============================================================
// MONTAR AS CÉLULAS DO CALENDÁRIO MENSAL
// ============================================================

// Array que armazenará todos os dias que aparecerão no calendário.
// Os valores null representam espaços vazios antes ou depois do mês.
$dias_calendario = [];


// Só monta o calendário se não existir erro
// e se o serviço tiver sido carregado corretamente.
if (!$erro && $servico) {

    // Cria um objeto representando o primeiro dia do mês.
    $primeiro_dia_mes = new DateTime(
        sprintf("%04d-%02d-01", $ano, $mes)
    );


    // Obtém o dia da semana correspondente ao primeiro dia do mês.
// O método format("w") retorna:
// 0 = domingo, 1 = segunda, ..., 6 = sábado.
    $dia_semana_inicio =
        (int)$primeiro_dia_mes->format("w");


    // Obtém a quantidade total de dias existentes no mês.
    // Por exemplo, fevereiro pode ter 28 ou 29 dias.
    $quantidade_dias =
        (int)$primeiro_dia_mes->format("t");


    // Adiciona espaços vazios antes do primeiro dia.
// Isso faz o calendário começar na coluna correta.
    for (
        $i = 0;
        $i < $dia_semana_inicio;
        $i++
    ) {
        $dias_calendario[] = null;
    }


    // Percorre todos os dias existentes no mês.
    for (
        $dia = 1;
        $dia <= $quantidade_dias;
        $dia++
    ) {

        // Adiciona cada número de dia ao calendário.
        $dias_calendario[] = $dia;
    }


    // Completa a última semana com espaços vazios.
// Assim a grade sempre mantém sete colunas.
    while (count($dias_calendario) % 7 !== 0) {
        $dias_calendario[] = null;
    }


    // Obtém o dia da semana correspondente à data selecionada.
// Esse valor será utilizado para consultar a jornada da profissional.
    $dia_semana_selecionado =
        (int)$data_obj_selecionada->format("w");


    try {

        // --------------------------------------------------------
        // VERIFICAR SE A DATA FOI LIBERADA
        // --------------------------------------------------------

        // Consulta se a profissional liberou a data escolhida
// para receber agendamentos.
        $consulta = $conexao->prepare("
            SELECT COUNT(*)
            FROM liberacao_agenda
            WHERE profissional_id = :pid
              AND :data_inicio >= data_inicio
              AND :data_fim <= data_fim
        ");


        // Envia a profissional e a data para a consulta.
        $consulta->execute([
            ":pid" => $profissional_id,
            ":data_inicio" => $data_selecionada,
            ":data_fim" => $data_selecionada
        ]);


        // fetchColumn() retorna diretamente o primeiro valor da consulta.
// Se for maior que zero, significa que existe uma liberação.
        $liberada =
            (int)$consulta->fetchColumn() > 0;


        // --------------------------------------------------------
        // CARREGAR A JORNADA DA PROFISSIONAL
        // --------------------------------------------------------

        // Busca os horários de trabalho configurados
// para o dia da semana selecionado.
        $consulta = $conexao->prepare("
            SELECT
                trabalha,
                horario_inicio,
                ultimo_horario,
                inicio_almoco,
                fim_almoco
            FROM horarios
            WHERE profissional_id = :pid
              AND dia_semana = :dia
            LIMIT 1
        ");


        // Envia o ID da profissional e o dia da semana.
        $consulta->execute([
            ":pid" => $profissional_id,
            ":dia" => $dia_semana_selecionado
        ]);


        // Recupera a configuração da jornada.
        $jornada =
            $consulta->fetch(PDO::FETCH_ASSOC);


        // --------------------------------------------------------
        // VALIDAR SE O DIA PODE RECEBER AGENDAMENTOS
        // --------------------------------------------------------

        // Caso a data seja anterior ao dia atual,
// informa que a cliente precisa escolher uma data válida.
        if ($data_selecionada < $data_hoje) {

            $status_dia =
                "Escolha uma data de hoje em diante.";


        // Se a profissional ainda não liberou aquela data,
// a cliente não pode realizar o agendamento.
        } elseif (!$liberada) {

            $status_dia =
                "Esta data ainda não foi liberada para agendamentos.";


        // Verifica se existe jornada configurada
// e se a profissional trabalha naquele dia.
        } elseif (
            !$jornada ||
            (int)$jornada["trabalha"] !== 1
        ) {

            $status_dia =
                "A profissional não atende neste dia.";


        // Verifica se os horários de início e término
// da jornada estão configurados.
        } elseif (
            empty($jornada["horario_inicio"]) ||
            empty($jornada["ultimo_horario"])
        ) {

            $status_dia =
                "Os horários de atendimento ainda não foram configurados.";

        } else {

            // ----------------------------------------------------
            // PREPARAR O INTERVALO DE TRABALHO
            // ----------------------------------------------------

            // Cria o horário inicial da jornada,
// combinando a data selecionada com o horário cadastrado.
            $inicio_jornada = new DateTime(
                $data_selecionada . " " .
                $jornada["horario_inicio"]
            );


            // Cria o horário final da jornada.
            $fim_jornada = new DateTime(
                $data_selecionada . " " .
                $jornada["ultimo_horario"]
            );


            // Define que as opções de horário serão geradas
// em intervalos de 30 minutos.
            $passo_minutos = 30;


            // Obtém data e horário atuais.
// Isso será usado para impedir horários que já passaram hoje.
            $agora = new DateTime();


            // Se existir início de almoço,
// cria um objeto DateTime correspondente.
            $inicio_almoco =
                !empty($jornada["inicio_almoco"])
                    ? new DateTime(
                        $data_selecionada . " " .
                        $jornada["inicio_almoco"]
                    )
                    : null;


            // Se existir fim de almoço,
// cria um objeto DateTime correspondente.
            $fim_almoco =
                !empty($jornada["fim_almoco"])
                    ? new DateTime(
                        $data_selecionada . " " .
                        $jornada["fim_almoco"]
                    )
                    : null;


            // ----------------------------------------------------
            // BUSCAR AGENDAMENTOS EXISTENTES
            // ----------------------------------------------------

            // Busca todos os agendamentos da profissional
// para aquela data que ainda não foram cancelados.
            $consulta = $conexao->prepare("
                SELECT
                    a.horario,
                    s.duracao
                FROM agendamentos a
                LEFT JOIN servicos s
                    ON s.id = a.servico_id
                WHERE a.profissional_id = :pid
                  AND a.data_agendamento = :data
                  AND a.status <> 'cancelado'
            ");


            // Envia o ID da profissional e a data consultada.
            $consulta->execute([
                ":pid" => $profissional_id,
                ":data" => $data_selecionada
            ]);


            // Recupera todos os agendamentos encontrados.
            $agendamentos_existentes =
                $consulta->fetchAll(PDO::FETCH_ASSOC);


            // ----------------------------------------------------
            // BUSCAR BLOQUEIOS
            // ----------------------------------------------------

            // Busca os horários bloqueados pela profissional
// naquele dia.
// Esses bloqueios podem representar folgas,
// compromissos ou outros períodos indisponíveis.
            $consulta = $conexao->prepare("
                SELECT
                    horario_inicio,
                    horario_fim
                FROM bloqueios_agenda
                WHERE profissional_id = :pid
                  AND data_bloqueio = :data
            ");


            // Envia os dados necessários para a consulta.
            $consulta->execute([
                ":pid" => $profissional_id,
                ":data" => $data_selecionada
            ]);


            // Recupera todos os bloqueios encontrados.
            $bloqueios =
                $consulta->fetchAll(PDO::FETCH_ASSOC);


            // ----------------------------------------------------
            // GERAR OS HORÁRIOS DISPONÍVEIS
            // ----------------------------------------------------

            // O cursor representa o horário que está sendo analisado.
// Ele começa no início da jornada.
            $cursor = clone $inicio_jornada;


            // Continua verificando horários até atingir
// o final da jornada.
            while (true) {

                // Calcula o horário em que o serviço terminaria
// caso começasse no horário atual do cursor.
                $fim_opcao =
                    (clone $cursor)
                    ->modify("+{$duracao_servico} minutes");


                // Se o serviço ultrapassar o final da jornada,
// não existe mais horário válido para começar o atendimento.
                if ($fim_opcao > $fim_jornada) {
                    break;
                }


                // Converte o horário atual para o formato HH:MM,
// que será mostrado para a cliente.
                $horario_texto =
                    $cursor->format("H:i");


                // Inicialmente considera o horário como disponível.
                $ocupado = false;


                // ------------------------------------------------
                // VERIFICAR HORÁRIO QUE JÁ PASSOU
                // ------------------------------------------------

                // Se a data selecionada for hoje
// e o horário já tiver passado,
// esse horário não pode ser escolhido.
                if (
                    $data_selecionada === $data_hoje &&
                    $cursor <= $agora
                ) {
                    $ocupado = true;
                }


                // ------------------------------------------------
                // VERIFICAR INTERVALO DE ALMOÇO
                // ------------------------------------------------

                // Verifica se o período do serviço cruza
// o intervalo de almoço da profissional.
                if (
                    $inicio_almoco &&
                    $fim_almoco &&
                    $cursor < $fim_almoco &&
                    $fim_opcao > $inicio_almoco
                ) {
                    $ocupado = true;
                }


                // ------------------------------------------------
                // VERIFICAR OUTROS AGENDAMENTOS
                // ------------------------------------------------

                // Percorre todos os agendamentos existentes
// para verificar se existe conflito de horário.
                foreach (
                    $agendamentos_existentes
                    as $agendamento
                ) {

                    // Cria o horário inicial do agendamento existente.
                    $inicio_existente = new DateTime(
                        $data_selecionada . " " .
                        $agendamento["horario"]
                    );


                    // Converte a duração do serviço existente
// para minutos.
                    $duracao_existente =
                        converterDuracaoMinutos(
                            $agendamento["duracao"] ?? ""
                        );


                    // Caso a duração não seja válida,
// utiliza 30 minutos como valor de segurança.
                    if ($duracao_existente <= 0) {
                        $duracao_existente = 30;
                    }


                    // Calcula o horário em que o agendamento existente termina.
                    $fim_existente =
                        (clone $inicio_existente)
                        ->modify(
                            "+{$duracao_existente} minutes"
                        );


                    // Verifica se o novo horário se sobrepõe
// ao agendamento existente.
// Se houver interseção entre os períodos,
// o horário é considerado ocupado.
                    if (
                        $cursor < $fim_existente &&
                        $fim_opcao > $inicio_existente
                    ) {

                        $ocupado = true;

                        // Como já encontramos um conflito,
// não precisamos verificar os outros agendamentos.
                        break;
                    }
                }


                // ------------------------------------------------
                // VERIFICAR BLOQUEIOS E FOLGAS
                // ------------------------------------------------

                // Percorre todos os bloqueios cadastrados para o dia.
                foreach ($bloqueios as $bloqueio) {

                    // Cria o início do período bloqueado.
                    $inicio_bloqueio = new DateTime(
                        $data_selecionada . " " .
                        $bloqueio["horario_inicio"]
                    );


                    // Cria o final do período bloqueado.
                    $fim_bloqueio = new DateTime(
                        $data_selecionada . " " .
                        $bloqueio["horario_fim"]
                    );


                    // Verifica se o horário do serviço
// se sobrepõe ao período bloqueado.
                    if (
                        $cursor < $fim_bloqueio &&
                        $fim_opcao > $inicio_bloqueio
                    ) {

                        $ocupado = true;

                        // Para a verificação porque já existe um conflito.
                        break;
                    }
                }


                // ------------------------------------------------
                // ADICIONAR HORÁRIO DISPONÍVEL
                // ------------------------------------------------

                // Só adiciona o horário à lista
// se nenhuma das verificações anteriores o marcou como ocupado.
                if (!$ocupado) {

                    // Adiciona o horário disponível ao array.
                    $horarios_dia[] = [

                        // Guarda o horário no formato HH:MM.
                        "horario" => $horario_texto,

                        // Cria o link que levará para a próxima
// etapa do processo de agendamento.
                        "link" =>
                            "confirmar_agendamento.php?"
                            . http_build_query([
                                "servico_id" =>
                                    $servico_id,

                                "profissional_id" =>
                                    $profissional_id,

                                "data" =>
                                    $data_selecionada,

                                "horario" =>
                                    $horario_texto
                            ])
                    ];
                }


                // Avança o cursor em 30 minutos
// para testar a próxima possibilidade de horário.
                $cursor->modify(
                    "+{$passo_minutos} minutes"
                );
            }


            // Depois de testar todos os horários,
// verifica se algum horário realmente ficou disponível.
            if (empty($horarios_dia)) {

                // Caso nenhum horário esteja livre,
// apresenta uma mensagem para a cliente.
                $status_dia =
                    "Não há horários disponíveis para esta data.";
            }
        }

    } catch (Throwable $e) {

        // Registra o erro técnico no log do servidor.
        error_log(
            "Erro ao carregar disponibilidade da agenda: "
            . $e->getMessage()
        );

        // Mostra uma mensagem simples e amigável para a cliente.
        $status_dia =
            "Não foi possível consultar os horários desta data.";
    }
}

?>