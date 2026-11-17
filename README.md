# Urbs: participação cidadã

Plataforma comunitária e independente para melhorar a cidade:

1. **Aponte**: moradores registram problemas e sugestões no mapa, com foto e localização.
2. **Proponha**: qualquer pessoa (arquitetos, engenheiros, estudantes, ilustradores) envia propostas para aquele lugar, com antes e depois. Profissionais com CAU/CREA conferido recebem um selo de verificado.
3. **Compare**: a comunidade compara as propostas para o mesmo lugar e indica a sua preferida.

As propostas formam um acervo permanente de ideias para a cidade, com a autoria preservada.

É um PWA (instala no celular como app, sem loja), feito em **PHP + MySQL** e pensado para rodar em **hospedagem compartilhada** barata.

---

## Funcionalidades

| Módulo | O que tem |
|---|---|
| Cadastro e perfis | Morador, profissional, estudante, ilustrador, associação; confirmação de e-mail; selos de **profissional verificado** (CAU/CREA) e **morador verificado** (comprovante de endereço, apagado após a análise) |
| Mapa | Leaflet + OpenStreetMap, marcadores por categoria, filtros, "toque para registrar aqui", localização do aparelho |
| Ocorrências | Problema ou sugestão, até 5 fotos, local no mapa com endereço automático, aviso de ocorrências parecidas por perto, apoio (+1), comentários, status com histórico |
| Propostas | Antes/depois com controle deslizante, galeria, PDFs (plantas), custo estimado, licença escolhida pelo autor (Creative Commons ou todos os direitos reservados) |
| Comparação | Lado a lado com a situação atual; uma preferência por pessoa por lugar, que pode ser trocada; resultados de moradores verificados mostrados à parte; aviso de resultado não vinculante |
| Painel público | Números, gráficos por status e categoria, linha do tempo |
| Moderação | Fila para contas novas, denúncias, verificação de selos, bloqueio de usuários, papéis (moderador/admin), configurações, **registro público de ações** (transparência) |
| LGPD | Política de privacidade, consentimento no cadastro, exportação de dados em JSON, exclusão de conta (com opção de apagar ou anonimizar as publicações), EXIF/GPS removido das fotos |
| PWA | Instalável, funciona com conexão ruim, página offline, fotos comprimidas no próprio celular antes do envio |

### Proteções contra manipulação e abuso

- Um apoio por conta por ocorrência e uma preferência por conta por lugar.
- E-mail confirmado obrigatório para publicar, apoiar, votar e comentar.
- Limite de 3 cadastros por IP por dia, campo invisível anti-robô e limites de publicações e comentários por conta.
- As primeiras publicações de cada conta passam por moderação. O número é configurável.
- Apoios e preferências de moradores verificados aparecem separados do total.
- Proteção CSRF em todos os formulários, senhas com `password_hash`, cabeçalhos de segurança (CSP, X-Frame-Options), uploads recodificados e validados pelo conteúdo, pasta de uploads sem execução de scripts.

---

## Instalação em hospedagem compartilhada

**Requisitos:** PHP 8.1+ com as extensões `pdo_mysql` (ou `pdo_sqlite`), `gd`, `fileinfo` e `mbstring`; MySQL 5.7+ ou MariaDB 10.3+; Apache com `mod_rewrite` (recomendado). Praticamente toda hospedagem brasileira de PHP atende.

1. No painel da hospedagem, crie um banco MySQL e um usuário com acesso a ele.
2. Envie todos os arquivos deste repositório por FTP para a pasta do site (ex.: `public_html/` ou uma subpasta).
3. Garanta que as pastas `uploads/` e `storage/` e a pasta raiz tenham permissão de escrita (normalmente 755).
4. Acesse `https://seu-dominio/install.php` e preencha o formulário: cidade, centro do mapa, banco, e-mail e conta de administrador.
5. Apague o `install.php` do servidor depois de instalar.
6. Ative o **HTTPS** no painel da hospedagem (Let's Encrypt costuma ser gratuito). A câmera, o GPS e a instalação como app exigem HTTPS.

Se as páginas internas derem erro 404, o servidor não tem `mod_rewrite`. Apague o `config.php`, rode o instalador de novo e desmarque "URLs amigáveis".

### Depois de instalar

Entre como administrador e abra **Moderação → Configurações** para:

- **Tiles do mapa.** Os tiles públicos do OpenStreetMap servem para testes, mas a [política de uso](https://operations.osmfoundation.org/policies/tiles/) não permite uso intenso em produção. Crie uma chave gratuita no [MapTiler](https://www.maptiler.com/) ou no [Stadia Maps](https://stadiamaps.com/) e cole a URL, por exemplo `https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=SUA_CHAVE`.
- Ajustar categorias, textos da página "Sobre" e o e-mail de contato.
- Definir quantas publicações aprovadas uma conta precisa ter para publicar sem moderação.

**E-mail:** a função `mail()` da hospedagem funciona, mas pode cair no spam. Para melhor entrega, use SMTP (Brevo, Mailgun, Amazon SES ou o SMTP da própria hospedagem). Configure no instalador ou editando `config.php`.

**Backup:** faça cópias periódicas do banco (pelo painel da hospedagem) e da pasta `uploads/`.

### Nginx

Se usar Nginx em vez de Apache, aponte tudo para o `index.php` e bloqueie as pastas internas:

```nginx
location ~ ^/(app|storage|scripts)/ { deny all; }
location = /config.php { deny all; }
location ~ ^/uploads/.*\.(php|phtml|phar)$ { deny all; }
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

---

## Desenvolvimento local

Não precisa de Composer, Node nem banco instalado:

```bash
php -S 127.0.0.1:8000 index.php
```

Abra `http://127.0.0.1:8000/install.php`, escolha **SQLite** e o envio de e-mail **"gravar em storage/logs/mail.log"**. Os links de confirmação de e-mail aparecem nesse arquivo.

Para ver erros detalhados, ponha `'debug' => true` no `config.php`.

### Estrutura

```
index.php              roteador (todas as rotas estão listadas aqui)
install.php            instalador web
sw.js                  service worker do PWA
app/bootstrap.php      configuração, sessão, cabeçalhos de segurança
app/controllers/       páginas e ações, agrupadas por assunto
app/lib/               banco, autenticação, uploads, e-mail, regras de negócio
app/views/             templates PHP
app/schema/mysql.sql   esquema do banco (o SQLite é derivado dele na instalação)
assets/                CSS, JS, ícones e Leaflet (servidos localmente)
uploads/               fotos e PDFs públicos (sem execução de scripts)
storage/               logs, banco SQLite e documentos privados de verificação
```

PHP puro, sem framework, de propósito: qualquer pessoa consegue ler, alterar e hospedar em qualquer lugar.

---

## Próximos passos possíveis

- Verificação de telefone por SMS (OTP). Exige um provedor pago de SMS; o ponto de entrada seria o cadastro em `app/controllers/auth.php`.
- Armazenamento de arquivos em S3/Cloudflare R2 quando o disco da hospedagem ficar pequeno.
- Notificações por WhatsApp.
- Integração com o SAPL da Câmara Municipal para importar projetos de lei.
- Visualização 3D das propostas com `<model-viewer>` (glTF).

## Licença

[GNU AGPL-3.0](LICENSE). Você pode usar, estudar, modificar e instalar em qualquer cidade. Se oferecer uma versão modificada ao público pela internet, precisa disponibilizar o código-fonte das modificações sob a mesma licença.

O Leaflet (em `assets/vendor/leaflet`) é distribuído sob a licença BSD-2-Clause.
