<?php
/*
Plugin Name: WooCommerce Akatus Oficial
Plugin URI: https://github.com/Akatus/woocommerce
Description: Plugin oficial de integração com a Akatus via Woocommerce. Inicialmente baseado no plugin desenvolvido por John Henrique (http://johnhenrique.com/plugin/woocommerce-akatus/).
Version: beta
Author: Akatus
Author URI: http://akatus.com/
License: GPL2

Requires at least: 3.5
Tested up to: 3.5.2
Text domain: wc-akatus
*/

/*  Copyright 2013  Akatus
    Copyright 2013  John-Henrique  (email : para@johnhenrique.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define("AGUARDANDO_PAGAMENTO",  "Aguardando Pagamento");
define("EM_ANALISE",            "Em Análise");
define("COMPLETO",              "Completo");
define("APROVADO",              "Aprovado");
define("ESTORNADO",             "Estornado");
define("DEVOLVIDO",             "Devolvido");
define("CANCELADO",             "Cancelado");

define("PENDING",               "pending");
define("FAILED",                "failed");
define("ON_HOLD",               "on-hold");
define("PROCESSING",            "processing");
define("COMPLETED",             "completed");
define("REFUNDED",              "refunded");
define("CANCELLED",             "cancelled");

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	add_action('plugins_loaded', 'woocommerce_gateway_akatus', 0);
	
	function woocommerce_gateway_akatus(){
		if( !class_exists( 'WC_Payment_Gateway' ) ) return;
	
		load_plugin_textdomain('WC_Akatus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
		add_filter('woocommerce_payment_gateways', 'add_akatus_gateway' );
	
		class WC_Gateway_Akatus extends WC_Payment_Gateway {
			
			public function __construct() {
				global $woocommerce;
				
				$this->versao		= 'beta';
				
				$this->id 			= 'akatus';
		        $this->method_title = 'Akatus';
				$this->has_fields 	= true;
                $this->nip_url      = site_url() . '/?wc-api=WC_Gateway_Akatus';

				$this->init_form_fields();
				$this->init_settings();
				
				$this->title 		= $this->settings['title'];
				$this->description 	= $this->settings['description'];
				$this->nip 			= $this->settings['nip'];
				$this->key 			= $this->settings['key'];
				$this->email		= $this->settings['email'];
				$this->debug		= $this->settings['debug'];	
				$this->ambiente		= $this->settings['ambiente'];
				$this->payment_type	= $this->settings['payment_type'];

				if( ( $this->ambiente == 'dev' ) or ( $this->ambiente == '' ) ){
					$this->ambiente = 'dev';
				}else{
					$this->ambiente = 'www';
				}
				
				if ($this->debug=='yes') $this->log = $woocommerce->logger();
				
				if ( !$this->is_valid_currency() || !$this->are_token_set() )
					$this->enabled = false;
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
					add_action( 'woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
					add_action('init', array(&$this, 'notificacao' ));
				} else {
					add_action( 'woocommerce_update_options_payment_gateways_'. $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_api_'. strtolower( get_class( $this ) ), array( $this, 'notificacao' ) );
				}
	
				add_action('woocommerce_receipt_akatus', array(&$this, 'receipt_page' ));

                wp_enqueue_script('akatus', plugin_dir_url(__FILE__) . 'js/akatus.js', array('jquery'), null, true);
			}

		    public function init_form_fields() {
		    
		    	$this->form_fields = array(
					'enabled' => array(
                            'title' => 'Habilitado', 
                            'type' => 'select', 
                            'options' => array('yes' => 'Sim', 'no' => 'Não'),
                            'default' => 'yes'
                        ), 
					'title' => array(
                            'title' => 'Título',
                            'type' => 'text', 
                            'description' => 'Esse é o texto que aparecerá durante o checkout.',
                            'default' => 'Akatus'
                        ),
					'description' => array(
                            'title' => 'Descrição', 
                            'type' => 'textarea', 
                            'description' => 'Texto para mostrar no checkout, acima dos meios de pagamento.',
                            'default' => 'Pague com boleto bancário.',
                        ),
					'email' => array(
                            'title' => 'E-mail',
                            'type' => 'text', 
                            'description' => 'O e-mail utilizado no cadastro da Akatus.', 
                            'default' => ''
                        ),
					'nip' => array(
                            'title' => 'Token NIP', 
                            'type' => 'text', 
                            'description' => 'O Token disponibilizado na área autenticada da sua conta Akatus.', 
                            'default' => ''
                        ),
					'key' => array(
                            'title' => 'API Key', 
                            'type' => 'text', 
                            'description' => 'A API Key disponibilizada na área autenticada da sua conta Akatus.', 
                            'default' => ''
                        ),
					'ambiente' => array(
                            'title' => 'Ambiente', 
                            'type' => 'select', 
                            'options' => array(
                                'dev' => 'Sandbox', 
                                'www' => 'Produção' 
                            ),
                            'description' => 'Com o ambiente sandbox é possível realizar transações de teste, utilizando uma conta específica criada em http://dev.akatus.com/', 
                            'default' => 'www'
                        ),
					'debug' => array(
                            'title' => 'Habilitar Log?', 
                            'type' => 'checkbox', 
                            'description' => '* Criar o diretório <code>wp-content/plugins/woocommerce/logs</code>', 
                            'default' => ''
                        ),
                    'invoice_prefix' => array( 
                            'title' => 'URL para Notificação Instantânea de Pagamento (NIP)',
                            'type' => 'text',
                            'description' => 'Esse é o endereço que deverá ser cadastrado na sua conta Akatus (Redirecionamentos, campo notificação de pagamentos instantânea)',
                            'default' => $this->nip_url,
                        )
                                
					);
		    
		    }
		    
			public function admin_options() {
		    	?>
		    	<table class="form-table">
					<?php if ( ! $this->is_valid_currency() ) : ?>
						<div class="inline error">
                            <p><strong><?php _e( 'Gateway Disabled', 'WC_Akatus' ); ?></strong>: A Akatus não oferece suporte para a moeda da sua loja. É necessário selecionar o Real Brasileiro.</p>
						</div>
					<?php endif; ?>
					
					<?php if ( ! $this->are_credentials_set() ) : ?>
						<div class="inline error">
							<p><strong><?php _e( 'Gateway Disabled', 'WC_Akatus' ); ?></strong>: You must give the token of your account email.</p>
						</div>
					<?php endif; ?>

                <?php $this->generate_settings_html(); ?>

                </table>

		    	<?php
		    }
		    
			protected function are_token_set(){
                if( empty( $this->key ) ) {
					return false;
                }
                
                return true;
			}
			
			function is_valid_currency() {
				if ( !in_array( get_option( 'woocommerce_currency' ), array( 'BRL' ) ) )
					return false;
				else
					return true;
			}
	
			function are_credentials_set() {
				if( empty( $this->email ) || empty( $this->key ) || empty( $this->nip ) )
					return false;
				else
					return true;
			}
		    
            function payment_fields() {
                global $woocommerce;
                $cartoes_credito = array();
                $bancos_tef = array();

                if ($this->description) echo wpautop(wptexturize($this->description)); 
                
                $xml = $this->get_meios_pagamento();

                if (strval($xml->status) === 'erro') {
                    die("<p>Nenhuma forma de pagamento disponível.</p>");
                }

                foreach ($xml->meios_de_pagamento->meio_de_pagamento as $meio_de_pagamento) {
                    if(strval($meio_de_pagamento->descricao) === 'Boleto Bancário') {
                        echo "<div>";
                        echo "  <input type='radio' name='akatus' value='boleto'><label><strong>Boleto</strong></label>";
                        echo "</div>";
                    }

                    if(strval($meio_de_pagamento->descricao) === 'TEF') {
                        echo "<div style='margin: 5px 0;'>";
                        echo "  <input type='radio' name='akatus' value='tef'><label><strong>Transferência Eletrônica</strong></label>";
                        echo "</div>";

                        echo "<div id='tef' style='display: none; margin: 15px 12px;'>";
                        echo "<p style='margin-bottom: 1em;'><strong>* Pode ser necessário desabilitar o bloqueio de popup</strong></p>";

                            foreach ($meio_de_pagamento->bandeiras->bandeira as $bandeira) {
                                $codigo = strval($bandeira->codigo);
                                $descricao = substr(strval($bandeira->descricao), 6, strlen($bandeira->descricao));
                                echo "<div>";
                                echo "  <input type='radio' name='tef' value='$codigo'><label>$descricao</label>";
                                echo "</div>";
                            }

                        echo "</div>";
                    }

                    if(strval($meio_de_pagamento->descricao) === 'Cartão de Crédito') {
                        foreach ($meio_de_pagamento->bandeiras->bandeira as $bandeira) {
                            $cartoes_credito[strval($bandeira->codigo)] = strval($bandeira->descricao);
                        }
                    }
                }

                if (! empty($cartoes_credito)) {
                    echo "<div style='margin: 5px 0;'>";
                    echo "  <input type='radio' name='akatus' value='cartao'><label><strong>Cartão de Crédito</strong></label>";
                    echo "</div>";
                        
                    $parcelamento = $this->get_parcelamento();

                    $juros =  str_replace("% ao mês","", $parcelamento["resposta"]["descricao"]);
                    $parcelas_assumidas = $parcelamento["resposta"]["parcelas_assumidas"];

                    echo "<div id='cartao' style='display: none; margin-left: 12px;' class='col2-set'>";

                        echo "<div style='margin: 15px 0;'>";
                        foreach ($cartoes_credito as $codigo => $descricao) {
                            echo "<input type='radio' name='bandeira_cartao' value='$codigo' id='$codigo' />";
                            echo "<img src='" .plugin_dir_url(__FILE__). "imagens/$codigo.gif' style='cursor: pointer; margin-right: 2em;' class='bandeira' data-input='$codigo'>";
                        }
                        echo "</div>";

                        echo "<div class='col-1'>";

                            echo "<p class='form-row'>";
                            echo "  <label for='nome_cartao'>Nome do portador <abbr title='required' class='required'>*</abbr></label>";
                            echo "  <input type='text' name='nome_cartao' id='nome_cartao' class='input-text'>";
                            echo "</p>";

                            echo "<p class='form-row'>";
                            echo "  <label for='cpf_cartao'>CPF do portador <abbr title='required' class='required'>*</abbr></label>";
                            echo "  <input type='text' name='cpf_cartao' id='cpf_cartao' maxlength='14' class='input-text'>";
                            echo "</p>";

                            echo "<p class='form-row'>";
                            echo "  <label for='numero_cartao'>Número do cartão <abbr title='required' class='required'>*</abbr></label>";
                            echo "  <input type='text' name='numero_cartao' id='numero_cartao' maxlength='20' class='input-text'>";
                            echo "</p>";
             
                            echo "<p class='form-row'>";
                            echo "  <label for='cvv_cartao'>CVV <abbr title='required' class='required'>*</abbr></label>";
                            echo "  <input type='text' name='cvv_cartao' id='cvv_cartao' maxlength='4' size='6' class='input-text'>";
                            echo "</p>";

                            echo "<p class='form-row'>";
                            echo "  <label for='mes_validade_cartao'>Data de validade <abbr title='required' class='required'>*</abbr></label>";
                            echo "  <select name='mes_validade_cartao'>";
                            echo "      <option value=''>Mês</option>";
                            for ($mes = 1; $mes <= 12; $mes++) {
                                echo "  <option value='$mes'>" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "</option>";
                            }
                            echo "  </select>";

                            echo "  <select name='ano_validade_cartao'>";
                            echo "      <option value=''>Ano</option>";
                            $anoAtual = intval(date('Y'));
                            for ($ano = $anoAtual; $ano <= $anoAtual + 15; $ano++) {
                                echo "  <option value='$ano'>$ano</option>";
                            }
                            echo "  </select>";
                            echo "</p>";

                            echo "<p class='form-row-first'>";
                            echo "<label for='parcelas_cartao'>Parcelas <abbr title='required' class='required'>*</abbr></label>";
                            if(! empty($parcelamento['resposta']['parcelas'])) { 

                            echo "<select name='parcelas_cartao'>";

                            $i = 1;
                            foreach($parcelamento["resposta"]["parcelas"] as $parcela) {

                                if($i > 1 && $i > $parcelas_assumidas){
                                   $aviso_juros = "(".$juros."% a.m)";
                                } else {
                                   $aviso_juros = " sem juros";
                                }

                                $valor_parcela_formatada = number_format($parcela['valor'], 2, ",", ".");

                                if($parcela['valor'] < 5 && $aviso_juros != " sem juros")
                                    continue;

                                echo "<option value='{$i}'>{$i}x de R$ {$valor_parcela_formatada} {$aviso_juros}</option>";

                                $i++;
                            }

                            echo "</select>";

                            } else {
         
                                echo "<select name='parcelas_cartao'>";
                                echo "  <option value='1'>1x de R$ " . number_format($woocommerce->cart->total, 2, ",", ".") . "</option>";
                                echo "</select>";
                            }

                            echo "</p>";

                        echo "</div>";

                        echo "<div class='col-2'>";
                        echo "</div>";

                    echo "</div>";
                }
		    }
		    
            public function validate_fields() {
                global $woocommerce;
                $tipo_pagamento = $_POST['akatus'];

                if ($tipo_pagamento) {

                    if ($tipo_pagamento === 'cartao') {
                        $bandeira_cartao = $_POST['bandeira_cartao'];
                        $nome_cartao = $_POST['nome_cartao'];
                        $numero_cartao = $_POST['numero_cartao'];
                        $cpf_cartao = $_POST['cpf_cartao'];
                        $cvv_cartao = $_POST['cvv_cartao'];
                        $mes_validade_cartao = $_POST['mes_validade_cartao'];
                        $ano_validade_cartao = $_POST['ano_validade_cartao'];
                        $parcelas_cartao = $_POST['parcelas_cartao'];
                        $telefone_cartao = $_POST['billing_phone'];

                        if (! $bandeira_cartao) $woocommerce->add_error('Por favor, escolha uma das bandeiras disponíveis.');
                        if (! $nome_cartao) $woocommerce->add_error('Por favor, preencha o nome do titular do cartão de crédito.');
                        if (! preg_match('/\d{13,16}/', $numero_cartao)) $woocommerce->add_error('Por favor, preencha corretamente o número do cartão (somente os dígitos).');
                        if (! preg_match('/\d{11}/', $cpf_cartao)) $woocommerce->add_error('Por favor, preencha corretamente o CPF do titular do cartão (somente os dígitos).');
                        if (! preg_match('/\d{3,4}/', $cvv_cartao)) $woocommerce->add_error('Por favor, preencha corretamente o CVV do cartão (somente os dígitos).');
                        if (! $mes_validade_cartao) $woocommerce->add_error('Por favor, escolha o mês de validade do cartão.');
                        if (! $ano_validade_cartao) $woocommerce->add_error('Por favor, escolha o ano de validade do cartão.');
                        if (! $parcelas_cartao) $woocommerce->add_error('Por favor, escolha o número de parcelas.');

                        $woocommerce->session->payment_type = $bandeira_cartao;
                        $woocommerce->session->nome_cartao = $nome_cartao;
                        $woocommerce->session->numero_cartao = $numero_cartao;
                        $woocommerce->session->cpf_cartao = $cpf_cartao;
                        $woocommerce->session->cvv_cartao = $cvv_cartao;
                        $woocommerce->session->mes_validade_cartao = $mes_validade_cartao;
                        $woocommerce->session->ano_validade_cartao = $ano_validade_cartao;
                        $woocommerce->session->parcelas_cartao = $parcelas_cartao;
                        $woocommerce->session->telefone_cartao = $telefone_cartao;

                    } else if ($tipo_pagamento === 'tef') {
                        $valor = $_POST['tef'];

                        if ($valor) {
                            $woocommerce->session->payment_type = $valor;
                        } else {
                            $woocommerce->add_error('Por favor selecione uma das opções de transferência eletrônica.');
                        }

                    } else {
                        $woocommerce->session->payment_type = $tipo_pagamento;
                    }
                    
                } else {
                    $woocommerce->add_error('Por favor escolha um dos meios de pagamento Akatus.');
                }

            } 
			
            public function receipt_page( $order_id ) {
                $html = '';
				global $woocommerce;

				$order = &new WC_Order( $order_id );
				$this->pedido_id = $order->id;

				$retorno = $this->existe_transacao( $order );

			    if ($this->debug=='yes') $this->log->add( $this->id, 'Retorno de existe_transacao: '. print_r( $retorno, true ) );

                switch ($woocommerce->session->payment_type) {
                    case 'boleto':
                        $html = '<form action="'. esc_url( $this->url_retorno( $retorno->url_retorno ) ) .'" method="get" id="akatus_payment_form" target="_blank">
                                <input type="submit" class="button-alt" id="submit_akatus_payment_form" value="Gerar Boleto" />
                                </form>';
                        break;

                    case 'tef_itau':
                    case 'tef_bradesco':
                    case 'tef_bb':
                        $html = '<form action="'. esc_url( $this->url_retorno( $retorno->url_retorno ) ) .'" method="get" id="akatus_payment_form" target="_blank">
                                <input type="submit" class="button-alt" id="submit_akatus_payment_form" value="Efetuar TEF" />
                                </form>';
                        break;
                    
                    default:
                        if(isset($retorno) && isset($retorno->status)) {

                            $status = strval($retorno->status);

                            if($status === 'Aguardando Pagamento' || $status === 'Em Análise' || $status === 'Aprovado') {
                                $html  ="<h3>Seu pedido foi realizado com sucesso.</h3>";
                            } else {
                                $html  ="<h3>Pagamento não autorizado. Consulte a sua operadora de cartão de crédito para maiores informações.</h3>";
                            }
                            
                            if ($this->debug=='yes') $this->log->add( $this->id, 'Token inválido: '. $retorno->descricao );

                        } else {
                            $html  ="<h3>Desculpe, não foi possível concluir o seu pedido.</h3>";
                            $html .="<p>Tente novamente. Se o problema persistir, entre em contato com o administrador da loja.</p>";
                            
                            if ($this->debug=='yes') $this->log->add( $this->id, 'Falha na requisição.' );
                        }

                        break;
                    }

                echo $html;

                $woocommerce->session->payment_type = null;
                $woocommerce->session->nome_cartao = null;
                $woocommerce->session->numero_cartao = null;
                $woocommerce->session->cpf_cartao = null;
                $woocommerce->session->cvv_cartao = null;
                $woocommerce->session->mes_validade_cartao = null;
                $woocommerce->session->ano_validade_cartao = null;
                $woocommerce->session->parcelas_cartao = null;
                $woocommerce->session->telefone_cartao = null;
			}
			
			protected function url_retorno($url = ''){
				if( $this->ambiente == 'dev' ){
					if ($this->debug=='yes') $this->log->add( $this->id, 'Corrigindo URL de desenvolvimento.' );
					
					return str_replace( 'https://www.akatus.com', 'https://'. $this->ambiente .'.akatus.com', $url );
				}
				
				return $url;
			}
			
			public function request_token_API($order){
				global $woocommerce;
	
				$order->billing_phone = str_replace(array('(', '-', ' ', ')'), '', $order->billing_phone);
				$ddd = substr($order->billing_phone, 0, 2 );
				$telefone = substr($order->billing_phone, 2 );
				
				$xml = '
				<carrinho>
				    <recebedor>
				        <api_key>'. $this->key .'</api_key>
				        <email>'. $this->email .'</email>
				    </recebedor>
				    <pagador>
				        <nome>'. $order->billing_first_name ." ". $order->billing_last_name .'</nome>
				        <email>'. $order->billing_email .'</email>
				        <enderecos>
				            <endereco>
				                <tipo>entrega</tipo>
				                <logradouro>'. $order->billing_address_1 .'</logradouro>
				                <numero></numero>
				                <bairro>'. $order->billing_address_2 .'</bairro>
				                <cidade>'. $order->billing_city .'</cidade>
				                <estado>'. $order->billing_state .'</estado>
				                <pais>BRA</pais>
				                <cep>'. $order->billing_postcode .'</cep>
				            </endereco>
				        </enderecos>
				        <telefones>
				            <telefone>
				                <tipo>residencial</tipo>
				                <numero>'. $order->billing_phone .'</numero>
				            </telefone>
				        </telefones>
				    </pagador>
				    <produtos>
				    	'. $this->order_itens() .' 
				    </produtos>
				    <transacao>
				        <desconto>0</desconto>
				        <peso>0</peso>
				        <frete>'. $order->get_shipping() .'</frete>
				        <moeda>BRL</moeda>
				        <referencia>'. $order->id .'</referencia>
                        <meio_de_pagamento>'. $woocommerce->session->payment_type .'</meio_de_pagamento>';

                if (preg_match('/^cartao/', $woocommerce->session->payment_type)) {
                    $xml .= '
                        <numero>'. $woocommerce->session->numero_cartao .'</numero>
						<parcelas>'. $woocommerce->session->parcelas_cartao .'</parcelas>
						<codigo_de_seguranca>'. $woocommerce->session->cvv_cartao .'</codigo_de_seguranca>
                        <expiracao>'. $woocommerce->session->mes_validade_cartao .'/'. $woocommerce->session->ano_validade_cartao .'</expiracao>
						<portador>
							<nome>'. $woocommerce->session->nome_cartao .'</nome>
							<cpf>'. $woocommerce->session->cpf_cartao .'</cpf>
							<telefone>'. $woocommerce->session->telefone_cartao .'</telefone>
						</portador>';
                
                }

                $xml .= '
				    </transacao>				    
				</carrinho>';
				
				if($this->debug=='yes') $this->log->add( $this->id, 'XML '. $xml );

				$target = 'https://'. $this->ambiente .'.akatus.com/api/v1/carrinho.xml';
				
				if($this->debug=='yes') $this->log->add( $this->id, 'Ambiente: '. $this->ambiente );
				if($this->debug=='yes') $this->log->add( $this->id, 'URL: '. $target );

                $curl = curl_init($target);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
                curl_setopt($curl, CURLOPT_POSTFIELDS, "$xml");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                $resposta = curl_exec($curl);
                curl_close($curl);

	        	if(! $resposta) {
	        		if($this->debug=='yes') $this->log->add( $this->id, 'Erro efetuar transacao com a Akatus: '. print_r($resposta, true));
	        		return false;
				}
                
                if($this->debug=='yes') $this->log->add( $this->id, 'Resposta recebida da Akatus: '. print_r($resposta, true));
	        	
				return $resposta;
			}
			
		    protected function existe_transacao( $order ){
		    	global $post;
		    	
                $respostaXML = $this->processa_retorno( $this->request_token_API( $order ) );

                if ($this->debug=='yes') $this->log->add( $this->id, 'Criando transação: '. $transacao );
                
                $carrinho 		= str_replace( '', '', $respostaXML->carrinho );
                $url_retorno	= strval($this->url_retorno( $respostaXML->url_retorno ));
                $transacao		= str_replace( '', '', $respostaXML->transacao );

                // código do carrinho na Akatus
                update_post_meta( $post->ID, 'akatus_carrinho', $carrinho );
                
                // código da transação na Akatus
                update_post_meta( $post->ID, 'akatus_transacao', $transacao );

                // Endereço do boleto na akatus
                update_post_meta( $post->ID, 'akatus_url_retorno', $url_retorno );
		    	
				return $respostaXML;
		    }
			
			protected function processa_retorno($resposta){
				global $post;
				
				if ($this->debug=='yes') $this->log->add($this->id, 'Processando XML retornado.');
				
				$respostaXML = simplexml_load_string($resposta);
				
				if($respostaXML->status == 'erro') {
					if($this->debug=='yes') $this->log->add($this->id, 'Houve um erro: '. $respostaXML->descricao);
				}
				
				return $respostaXML;
			}
			
            protected function get_payment_type() {
                global $woocommerce;

                if ($this->payment_type === 'cartao') {
                    return $woocommerce->session->bandeira_cartao;
                }

                return $this->payment_type;
            }			
			
			function process_payment($order_id = 0) {
				global $woocommerce;
				
				if ($this->debug=='yes') $this->log->add( $this->id, 'Processando pagamento (process_payment).' );
				if($this->debug=='yes') $this->log->add( $this->id, 'WooCommerce '. WOOCOMMERCE_VERSION );
				if($this->debug=='yes') $this->log->add( $this->id, 'WooCommerce '. get_class( $this ) .' '. $this->versao );
				
				$order = &new WC_Order($order_id);
	
				$woocommerce->cart->empty_cart();
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
					// Pre 2.0
					unset($_SESSION['order_awaiting_payment']);
				} else {
				    // 2.0
					unset( $woocommerce->session->order_awaiting_payment );
				}
				
				$order->add_order_note( __('Pedido recebido', 'WC_Akatus') );
				
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
				);
			}

			function notificacao() {
				global $woocommerce;

                if ($this->debug=='yes') $this->log->add( $this->id, 'Notificação de pagamento recebida: '. print_r( $post, true ) );
				
                if(isset($_POST) && $_POST['token'] === $this->nip) {
						
                    $transacao = get_post_meta( $_POST['referencia'], 'akatus_transacao' );
                    $pedido = new WC_Order( $_POST['referencia'] );
                    
                    if(isset($pedido->order_custom_fields)){
                        unset($pedido->order_custom_fields);
                    }
                    
                    if ($this->debug=='yes') $this->log->add( $this->id, 'Transação encontrada: '. print_r( $pedido, true ) );
                    
                    $novoStatus = $this->status_helper($_POST['status'], $pedido->status);

                    if ($novoStatus) {
                        if ($novoStatus === COMPLETED) {

                            $pedido->update_status(COMPLETED);

                            add_post_meta($pedido->id, '_paid_date', current_time('mysql'), true);

                            $dados = array(
                                'ID' => $pedido->id,
                                'post_date' => current_time('mysql', 0),
                                'post_date_gmt' => current_time('mysql', 1)
                            );
                            wp_update_post($dados);

                            if (apply_filters('woocommerce_payment_complete_reduce_order_stock', true, $pedido->id))
                                $pedido->reduce_order_stock();

                        } else if($novoStatus === CANCELLED) {
                            $pedido->cancel_order();
                        } else {
                            $pedido->update_status($novoStatus);
                        }
                    }
				}
			}
			
			protected function status_helper($statusRecebido, $statusAtual){
				global $woocommerce;

                switch ($statusRecebido){

					case AGUARDANDO_PAGAMENTO:
                        $listaStatus = array(PENDING);                

                        if (in_array($statusAtual, $listaStatus)) {
                            return ON_HOLD;
                        } else {
                            return false;
                        }

					case EM_ANALISE:
                        $listaStatus = array(PENDING);                

                        if (in_array($statusAtual, $listaStatus)) {
                            return ON_HOLD;
                        } else {
                            return false;
                        }

                    case COMPLETO:					
					case APROVADO:
                        $listaStatus = array(
                            PENDING,
                            ON_HOLD,
                            PROCESSING
                        );

                        if (in_array($statusAtual, $listaStatus)) {
                            return COMPLETED;
                        } else {
                            return false;
                        }  					
					
                    case CANCELADO:
                        $listaStatus = array(
                            PENDING,
                            FAILED,
                            ON_HOLD,
                            PROCESSING,
                            COMPLETED
                        );                

                        if (in_array($statusAtual, $listaStatus)) {
                            return CANCELLED;                    
                        } else {
                            return false;
                        } 

					case DEVOLVIDO:
                        $listaStatus = array(
                            PENDING,
                            FAILED,
                            ON_HOLD,
                            PROCESSING,
                            COMPLETED
                        );                

                        if (in_array($statusAtual, $listaStatus)) {
                            return REFUNDED;                    
                        } else {
                            return false;
                        } 

					case ESTORNADO:
                        $listaStatus = array(COMPLETED);                

                        if (in_array($statusAtual, $listaStatus)) {
                            return REFUNDED;                    
                        } else {
                            return false;
                        } 
						
					default:
                        return false;
				}
			}
			
			protected function order_itens(){
				$item_loop = 0;
				$xml = '';
				
				$pedido = new WC_Order( $this->pedido_id );
				
				foreach ( $pedido->get_items( ) as $item_pedido ){
					
					// verificando se existem itens
					if( $item_pedido['qty'] ){
					
						$item_loop++;
						
						// Preço do produto
						$item_preco = $pedido->get_item_subtotal( $item_pedido, false );
						
						$xml .= '
				        <produto>
				            <codigo>'. $item_pedido['product_id'] .'</codigo>
				            <descricao>'. $item_pedido['name'] .'</descricao>
				            <quantidade>'. $item_pedido['qty'] .'</quantidade>
				            <preco>'. number_format( $item_preco, 2, '.', '' ) .'</preco>
				            <peso>0</peso>
				            <frete>0</frete>
				            <desconto>0</desconto>
				        </produto>
						';
					}
				}
				
				return $xml;
			}
			
            protected function get_meios_pagamento() {
                $xml = '
                <meios_de_pagamento>
                    <correntista>
                        <api_key>'. $this->key .'</api_key>
                        <email>'. $this->email .'</email>
                    </correntista>
                </meios_de_pagamento>';

                if($this->debug=='yes') $this->log->add($this->id, 'XML pedindo meios de pagamento: '. $xml);
                
                $target = 'https://'. $this->ambiente .'.akatus.com/api/v1/meios-de-pagamento.xml';
                
                $resposta = wp_remote_post( $target, array( 
                    'method' 	=> 'POST', 
                    'body' 		=> $xml, 
                    'sslverify' => false, 
                ) );

                if( is_wp_error( $resposta ) ) {
                    if($this->debug=='yes') $this->log->add( $this->id, 'Erro no pedido de meios de pagamento: '. print_r( $resposta, true ) );
                    return false;
                }

                $meios_pagamento = simplexml_load_string($resposta['body']);
                if($this->debug=='yes') $this->log->add( $this->id, 'XML resposta dos meios de pagamento: '. print_r($meios_pagamento, true) );

                return $meios_pagamento;
            }

            protected function get_cartoes_credito($xml) {
                $cartoes_credito = array();

                foreach ($xml->meios_de_pagamento->meio_de_pagamento as $meio_de_pagamento) {
                    if(strval($meio_de_pagamento->descricao) === 'Cartão de Crédito') {
                        foreach ($meio_de_pagamento->bandeiras->bandeira as $bandeira) {
                            $cartoes_credito[strval($bandeira->codigo)] = strval($bandeira->descricao);
                        }
                    }
                }

                return $cartoes_credito;
            }

            protected function get_parcelamento() {
                global $woocommerce;

                $tokens = array(
                    '{EMAIL}',
                    '{API_KEY}',
                    '{AMOUNT}'
                );

                $valores = array(
                    $this->email,
                    $this->key,
                    $woocommerce->cart->total
                );

                $target = 'https://'. $this->ambiente .'.akatus.com/api/v1/parcelamento/simulacao.json?email={EMAIL}&amount={AMOUNT}&payment_method=cartao_master&api_key={API_KEY}';
                $target = str_replace($tokens, $valores, $target);

                $resposta = wp_remote_get($target);

                return json_decode($resposta['body'], $assoc = true);
            }
		}
	}

	function add_akatus_gateway( $methods ){
	    $methods[] = 'WC_Gateway_Akatus'; return $methods;
	}
}

?>
