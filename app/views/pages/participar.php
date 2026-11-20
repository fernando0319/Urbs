<?php
$city = setting('city_name');
$local = (string)setting('participation_text', '');
$items = [
    'adote' => [
        'icon' => 'home',
        'title' => 'Adote uma praça (e outros espaços públicos)',
        'what' => 'Programas municipais em que moradores, associações ou empresas assumem o cuidado de uma praça, canteiro, rotatória ou jardim: limpeza, jardinagem, pequenas melhorias e manutenção. O acordo é formalizado com a prefeitura por um termo de adoção ou de cooperação. O espaço continua público: quem adota cuida, mas não vira dono nem pode restringir o uso. Em troca, costuma poder instalar uma placa de reconhecimento.',
        'how' => 'Pergunte à prefeitura ou à Câmara se o município tem uma lei desse tipo e quais são as regras: quem pode adotar, o que pode ser feito e como pedir. Se não houver, um vereador pode propor a criação, ou a própria comunidade pode apresentar um projeto de lei de iniciativa popular (veja abaixo). Um projeto comunitário aqui no ' . setting('site_name') . ' pode seguir esse caminho para conseguir a autorização.',
        'law' => 'Lei municipal. As regras mudam de cidade para cidade; procure na Lei Orgânica e na legislação do município (muitas Câmaras publicam as leis no SAPL).',
    ],
    'lai' => [
        'icon' => 'search',
        'title' => 'Pedido de acesso à informação',
        'what' => 'Qualquer pessoa pode pedir informações a qualquer órgão público, sem precisar explicar o motivo: contratos, cronogramas de obras, orçamentos, respostas sobre um serviço que não foi feito.',
        'how' => 'Use o Serviço de Informação ao Cidadão (SIC ou e-SIC) da prefeitura ou da Câmara. O órgão tem 20 dias para responder, prorrogáveis por mais 10, e uma negativa precisa ser justificada. Cabe recurso. Exemplo: "Qual o cronograma de manutenção da Rua X e por que a reclamação do protocolo Y não foi atendida?"',
        'law' => 'Lei de Acesso à Informação (Lei federal nº 12.527/2011).',
    ],
    'ouvidoria' => [
        'icon' => 'message',
        'title' => 'Ouvidoria e pedidos de providência',
        'what' => 'Canal oficial para reclamações, denúncias, sugestões, elogios e pedidos de providência sobre serviços públicos. Gera protocolo e obriga o órgão a responder.',
        'how' => 'Registre pela ouvidoria do município (site, telefone ou presencialmente) e guarde o número do protocolo. A resposta deve sair em até 30 dias, prorrogáveis por mais 30. Vale anotar o protocolo nos comentários da ocorrência aqui, para todos acompanharem.',
        'law' => 'Código de Defesa do Usuário do Serviço Público (Lei federal nº 13.460/2017).',
    ],
    'peticao' => [
        'icon' => 'file',
        'title' => 'Petição e requerimento formal',
        'what' => 'Direito de qualquer pessoa de pedir formalmente algo ao poder público, em defesa de direitos ou contra ilegalidade ou abuso. É o caminho para pedir, por exemplo, autorização para uma melhoria num espaço público.',
        'how' => 'Faça um requerimento por escrito, com descrição clara do pedido, documentos (fotos, abaixo-assinado, projeto, orçamento) e contato. Protocole na prefeitura e guarde o comprovante. Nos projetos comunitários, o número do protocolo pode ser registrado na seção "Autorização".',
        'law' => 'Constituição Federal, art. 5º, inciso XXXIV, alínea "a".',
    ],
    'audiencias' => [
        'icon' => 'users',
        'title' => 'Audiências públicas do orçamento e do Plano Diretor',
        'what' => 'O município é obrigado a abrir para a população a discussão do Plano Diretor e das leis do orçamento: PPA (plano para quatro anos), LDO (diretrizes do ano seguinte) e LOA (orçamento anual). Além disso, a prefeitura presta contas das metas fiscais em audiência na Câmara a cada quatro meses.',
        'how' => 'É nessas audiências que se pede para uma obra entrar no orçamento. Acompanhe os editais no site da prefeitura e da Câmara, compareça e leve propostas escritas. As ocorrências mais apoiadas aqui são um bom argumento.',
        'law' => 'Estatuto da Cidade (Lei federal nº 10.257/2001, arts. 40, 43 e 44) e Lei de Responsabilidade Fiscal (Lei Complementar nº 101/2000, arts. 9º e 48).',
    ],
    'conselhos' => [
        'icon' => 'layers',
        'title' => 'Conselhos municipais',
        'what' => 'Órgãos com participação da sociedade civil que acompanham e fiscalizam políticas públicas: saúde, educação (FUNDEB), assistência social, direitos da criança e do adolescente, meio ambiente e, em alguns municípios, desenvolvimento urbano.',
        'how' => 'As reuniões costumam ser abertas ao público. Entidades da sociedade civil, como associações de moradores, podem disputar as vagas nas eleições dos conselhos. Pergunte à prefeitura quais conselhos existem, quando se reúnem e como são escolhidos os membros.',
        'law' => 'Por exemplo: Lei nº 8.142/1990 (saúde), Lei nº 14.113/2020 (FUNDEB), Lei nº 8.742/1993 (assistência social) e Lei nº 8.069/1990 (ECA). Cada conselho municipal também tem lei local.',
    ],
    'camara' => [
        'icon' => 'flag',
        'title' => 'Câmara Municipal e vereadores',
        'what' => 'As sessões são públicas. Vereadores podem apresentar indicações (pedidos formais ao prefeito, como "conserte a Rua X"), requerimentos de informação, projetos de lei e emendas ao orçamento. Muitas Câmaras também têm tribuna livre, um espaço para cidadãos e entidades falarem em plenário.',
        'how' => 'Leve a demanda a mais de um vereador, com dados: número de apoios, fotos, propostas e orçamentos. Pergunte se o regimento interno prevê tribuna livre e como se inscrever. Acompanhe as indicações e cobre as respostas.',
        'law' => 'Lei Orgânica do Município e Regimento Interno da Câmara (variam por cidade).',
    ],
    'iniciativa' => [
        'icon' => 'pencil',
        'title' => 'Projeto de lei de iniciativa popular',
        'what' => 'A própria população pode apresentar um projeto de lei de interesse do município, desde que reúna a assinatura de pelo menos 5% do eleitorado. Exemplo: criar a lei "Adote uma praça" ou regras para doações de melhorias.',
        'how' => 'Confira na Lei Orgânica os requisitos exatos (formato das assinaturas, dados exigidos, tramitação). Em municípios pequenos, 5% do eleitorado costuma ser um número alcançável. Peça ajuda à Câmara para redigir o texto no formato correto.',
        'law' => 'Constituição Federal, art. 29, inciso XIII, e Estatuto da Cidade, art. 43, inciso IV.',
    ],
    'parcerias' => [
        'icon' => 'shield',
        'title' => 'Associação de moradores e parcerias com a prefeitura',
        'what' => 'Uma associação com CNPJ pode fazer parcerias formais com o poder público: termos de colaboração e de fomento (com repasse de recursos) e acordos de cooperação (sem repasse, por exemplo para cuidar de um espaço). Pelo Procedimento de Manifestação de Interesse Social (PMIS), organizações, movimentos sociais e cidadãos podem propor ao município um projeto para que ele avalie abrir um chamamento público.',
        'how' => 'Formalize a associação (estatuto, ata, CNPJ) e mantenha as contas em dia. Com ela, fica mais fácil arrecadar com transparência, assinar termos com a prefeitura e representar a comunidade em conselhos.',
        'law' => 'Marco Regulatório das Organizações da Sociedade Civil (Lei federal nº 13.019/2014; PMIS nos arts. 18 a 21).',
    ],
    'voluntariado' => [
        'icon' => 'thumb',
        'title' => 'Voluntariado e mutirões',
        'what' => 'O trabalho voluntário em órgãos públicos ou em entidades sem fins lucrativos é regulado por lei. É feito com um termo de adesão e não cria vínculo de emprego. É o formato adequado para mutirões em espaços públicos.',
        'how' => 'Combine o mutirão com a prefeitura (autorização para o espaço, descarte de resíduos, segurança) ou com a associação responsável, e registre os participantes num termo de adesão.',
        'law' => 'Lei do Serviço Voluntário (Lei federal nº 9.608/1998).',
    ],
    'doacao' => [
        'icon' => 'download',
        'title' => 'Doação de materiais e serviços ao município',
        'what' => 'Moradores e empresas podem doar ao município materiais (cimento, mudas, bancos) ou serviços, para que a própria prefeitura execute a melhoria com suas equipes e máquinas.',
        'how' => 'A doação precisa ser aceita formalmente pela prefeitura e, conforme o caso, pode exigir autorização em lei, por exemplo quando vem com encargo. Protocole a oferta por escrito, descrevendo o que será doado e para qual finalidade, e peça a resposta formal.',
        'law' => 'Regras do município (Lei Orgânica e leis locais) e normas de direito administrativo. Vale consultar a procuradoria do município.',
    ],
    'controle' => [
        'icon' => 'chart',
        'title' => 'Fiscalização: Tribunal de Contas, Ministério Público e ação popular',
        'what' => 'Quando há suspeita de irregularidade com dinheiro público, como uma obra paga e não feita ou um contrato superfaturado, qualquer cidadão ou associação pode denunciar ao Tribunal de Contas do Estado. O Ministério Público recebe representações sobre serviços essenciais, meio ambiente e patrimônio público. A ação popular permite a qualquer cidadão pedir na Justiça a anulação de um ato que prejudique o patrimônio público.',
        'how' => 'Use a ouvidoria do Tribunal de Contas do Estado ou a promotoria da sua comarca, com documentos e fotos. A ação popular precisa de advogado e é isenta de custas, salvo má-fé.',
        'law' => 'Constituição Federal, art. 74, § 2º, e art. 75 (Tribunais de Contas); art. 5º, inciso LXXIII, e Lei nº 4.717/1965 (ação popular).',
    ],
    'op' => [
        'icon' => 'compare',
        'title' => 'Orçamento participativo',
        'what' => 'Em alguns municípios, a população decide diretamente onde aplicar parte do orçamento, em assembleias por bairro ou em votações. Não é obrigatório por lei federal.',
        'how' => 'Pergunte se o município tem orçamento participativo. Se não tiver, a ideia pode ser levada às audiências do orçamento ou virar projeto de lei.',
        'law' => 'Depende de lei ou programa municipal. O Estatuto da Cidade (art. 44) exige a gestão orçamentária participativa por meio de audiências e consultas.',
    ],
];
?>
<div class="narrow prose-page">
  <p class="eyebrow">Participação cidadã</p>
  <h1>Como participar da vida da cidade</h1>
  <p class="lead-sm">Participar vai muito além de votar a cada quatro anos. A lei garante vários caminhos para cobrar, propor, fiscalizar e até cuidar diretamente de espaços públicos em <?= e($city) ?>. Aqui estão os principais, do mais simples ao mais estruturado.</p>

  <?php if ($local !== ''): ?>
  <div class="card local-card">
    <h2><?= icon('pin') ?>Em <?= e($city) ?></h2>
    <div class="prose"><?= nl2p($local) ?></div>
  </div>
  <?php endif ?>

  <nav class="toc" aria-label="Nesta página">
    <?php foreach ($items as $k => $it): ?><a class="pill" href="#<?= e($k) ?>"><?= e($it['title']) ?></a><?php endforeach ?>
  </nav>

  <div class="flash flash-info small">
    Conteúdo informativo, não é orientação jurídica. As regras municipais (Lei Orgânica, leis locais e regimento da Câmara) variam de cidade para cidade e podem mudar. Para casos concretos, consulte a prefeitura, a Câmara, a Defensoria Pública ou um advogado.
  </div>

  <?php foreach ($items as $k => $it): ?>
  <section class="card participate-card" id="<?= e($k) ?>">
    <h2><?= icon($it['icon']) ?><?= e($it['title']) ?></h2>
    <h3>O que é</h3>
    <p><?= e($it['what']) ?></p>
    <h3>Como usar</h3>
    <p><?= e($it['how']) ?></p>
    <p class="law small"><strong>Base legal:</strong> <?= e($it['law']) ?></p>
  </section>
  <?php endforeach ?>

  <section class="card">
    <h2><?= icon('list') ?>Por onde começar</h2>
    <ol>
      <li>Registre a ocorrência aqui e junte apoios.</li>
      <li>Abra um pedido na ouvidoria e, se não houver resposta, um pedido de acesso à informação. Anote os protocolos nos comentários.</li>
      <li>Leve o caso a vereadores e peça uma indicação ao Executivo.</li>
      <li>Participe da audiência do orçamento e peça que a melhoria entre na LOA.</li>
      <li>Se a comunidade quiser fazer por conta própria, organize um <a href="<?= e(url('/projetos')) ?>">projeto comunitário</a>, com autorização formal.</li>
      <li>Se houver suspeita de irregularidade com dinheiro público, acione o Tribunal de Contas ou o Ministério Público.</li>
    </ol>
  </section>
</div>
