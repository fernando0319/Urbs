<?php $contact = setting('contact_email'); ?>
<div class="narrow prose-page">
<h1>Política de privacidade</h1>
<p class="muted small">Modelo inicial. Antes de usar publicamente, revise com um profissional de direito e complete os dados do controlador.</p>

<p>Esta política explica como <?= e(setting('site_name')) ?> trata dados pessoais, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018, LGPD).</p>

<h2>Quem é o controlador</h2>
<p>O controlador é o responsável pela operação desta instância da plataforma em <?= e(setting('city_name')) ?><?= $contact ? ', com contato em <a href="mailto:' . e($contact) . '">' . e($contact) . '</a>' : '' ?>.</p>

<h2>Quais dados coletamos e por quê</h2>
<ul>
  <li><strong>Cadastro</strong> (nome, e-mail, senha protegida por hash): para criar e proteger sua conta. O e-mail não é exibido publicamente.</li>
  <li><strong>Publicações</strong> (textos, fotos, localização do problema, propostas, comentários): para cumprir a finalidade da plataforma. Essas informações são públicas.</li>
  <li><strong>Apoios e preferências</strong>: para contar a opinião da comunidade. Os números são públicos, mas não mostramos quem apoiou ou votou.</li>
  <li><strong>Verificação</strong> (número de registro profissional; comprovante de endereço): para conceder selos. <strong>O comprovante é visto só pela moderação e apagado logo após a análise.</strong></li>
  <li><strong>Dados técnicos</strong> (IP, de forma cifrada, para limitar tentativas de abuso): ficam guardados por no máximo 2 dias.</li>
</ul>
<p>As bases legais são o consentimento, dado no cadastro, e o legítimo interesse em prevenir fraude e abuso.</p>

<h2>Fotos</h2>
<p>Ao enviar fotos, todos os metadados (EXIF, incluindo a localização GPS do aparelho) são removidos automaticamente. Fotos com rostos identificáveis ou placas de veículos não são permitidas e podem ser removidas.</p>

<h2>Compartilhamento</h2>
<p>Não vendemos nem compartilhamos dados pessoais com terceiros para fins comerciais. Usamos provedores de infraestrutura (hospedagem, envio de e-mail e mapas) só para operar o serviço.</p>

<h2>Cookies</h2>
<p>Usamos só um cookie de sessão, necessário para manter você conectado. Não usamos cookies de rastreamento ou publicidade.</p>

<h2>Seus direitos</h2>
<p>Na página <a href="<?= e(url('/conta')) ?>">Minha conta</a> você pode, a qualquer momento:</p>
<ul>
  <li>corrigir seus dados;</li>
  <li><strong>baixar uma cópia</strong> dos seus dados (portabilidade);</li>
  <li><strong>excluir sua conta</strong>, escolhendo se suas publicações serão apagadas ou ficarão anônimas.</li>
</ul>
<p>Para outros pedidos, como informações sobre o tratamento ou revogação de consentimento, entre em contato<?= $contact ? ' pelo e-mail ' . e($contact) : '' ?>.</p>

<h2>Segurança</h2>
<p>Senhas são guardadas com hash forte, a comunicação deve usar HTTPS e documentos de verificação ficam em área não acessível publicamente.</p>
</div>
