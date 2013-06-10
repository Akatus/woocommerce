=== WooCommerce Akatus ===
Contributors: John-Henrique, hostdesigner
Donate link: 
Tags: WooCommerce, Gateway, Akatus, Pagamento, Loja virtual, Ecommerce
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integração WooCommerce Akatus para pagamentos com cartão de crédito e boleto bancário

== Description ==

Este plugin integra o gateway <a href="http://goo.gl/ICz2O">Akatus</a> ao WooCommerce permitindo reaceber pagamentos via boleto bancário, transferência eletrônica e cartão de crédito. Esta é uma versão gratuita 
e não dispõe de todas as funções e ferramentas disponíbilizadas pelo <a href="http://goo.gl/ICz2O">gateway Akatus</a>, como por exemplo Akatus Invisivel (ou "Pagamento transparente"), trata-se de um meio de pagamento 
onde o comprador não sai da página da loja para finalizar o pagamento.

= Atenção =

É obrigatório ter uma conta confirmada no Gateway Akatus com acesso a API, você pode criar a sua na página <a href="http://goo.gl/ICz2O">Akatus API</a>. Você também precisa ter o plugin 
WooCommerce instalado em sua loja virtual para utilizar o WooCommerce Akatus.

A integração ocorre por meio de webservice XML, o que aumenta o nivel de segurança. Não é necessário ter SSL ativado na loja nem contrato com a operadora de cartão ou banco, basta <a href="http://goo.gl/ICz2O">cadastrar-se no site da Akatus</a> e configurar sua conta.

A Akatus libera o dinheiro em 14 dias.

== Installation ==

1. Faça upload do plugin "woocommerce-akatus.zip" para o diretório "/wp-content/plugins/" 
1. Ative o plugin na página "Plugins"
1. Acesse o menu WooCommerce -> Configurações -> Portais de pagamento -> Akatus
1. Configure o plugin com as informações de sua conta Akatus

== Frequently asked questions ==

= Este plugin funciona com quais versões do WooCommerce? =

O WooCommerce Akatus foi desenvolvido para funcionar no WooCommerce 2.0, mas ele possui suporte ao WooCommerce 1.6, independente da versão WooCommerce basta configurar o plugin e tudo está pronto.

= Preciso de suporte, onde consigo? =

A versão gratuita não oferece suporte via email ou ticket, mas você pode consultar a <a href="http://johnhenrique.com/plugin/woocommerce-akatus">documentação do plugin</a> para solucionar possíveis problemas. 
Por favor, entenda que este é um plugin gratuito, o autor não fica disponivel 24 horas por dia respondendo dúvidas, acredite, ele também trabalha e pode estar dormindo no horário que você estiver postando 
dúvidas, por isso, caso você envie uma dúvida, tenha calma e aguarde a resposta.

= O WooCommerce Akatus possui uma versão comercial? =

Sim, a versão comercial possui suporte via email, Skype e ticket, além de permitir mais de um meio de pagamento simultâneo.

== Screenshots ==

1. screenshot-1.jpg
1. screenshot-2.jpg

== Changelog ==

*** Akatus for WooCommerce Changelog ***

2013.03.28 - version 1.8
	* Adicionado os itens de endereço ao XML
	* Adicionado a versão do plugin no sistema de logs
	* Adicionado lista dos produtos existentes no pedido
	* Suporte ao WC 2.0
	* Alterado o texto de domínio para internacionalização
	* Alterado o ID do plugin de Akatus para akatus
	* 

2013.01.14 - version 1.7
	* Adicionada verificação entre valores cobrados na loja e no gateway, se o valor for diferente não altera o status do pedido
	* Corrigido o bug que criava dois registros de pagamento na Akatus

2013.01.03 - version 1.6
	* Corrigidas as chamadas para funções depreciadas
	* Corrigida declaração duplicada de 'process_payment'
	* Corrigido o processamento do XML quando algum erro é retornado da Akatus
	* Corrigido retorno de dados (NIP)

2013.01.02 - version 1.5
	* Texto de domínio corrigido
	* Adicionado tratamento a URL existente dentro da tag url_retorno do XML de pagamento (quando o ambiente é 'dev')
	* Corrigido o processamento do pagamento
	
2012.12.29 - version 1.0
	* Vesão inicial
	
	

== Upgrade notice ==

Este plugin é mantido e distribuido gratuitamente. O autor não se responsabiliza por problemas que ocorram durante o processo de atualização. Sempre realize backup de seus dados ANTES e DEPOIS de atualizar qualquer plugin.