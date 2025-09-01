<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verificar se há parâmetro de verificação automática na URL
$numero_verificar = isset($_GET['verificar_certificado']) ? sanitize_text_field($_GET['verificar_certificado']) : '';
?>

<div id="gc-verificar-certificado" class="gc-container">
    <div class="gc-header">
        <h2>🔐 <?php _e('Verificar Autenticidade de Certificado', 'gestao-coletiva'); ?></h2>
        <p><?php _e('Digite o número do certificado para verificar sua autenticidade e validade.', 'gestao-coletiva'); ?></p>
    </div>
    
    <div class="gc-verificacao-form">
        <div class="gc-card">
            <form id="gc-form-verificar">
                <div class="gc-campo-verificacao">
                    <label for="numero-certificado"><?php _e('Número do Certificado:', 'gestao-coletiva'); ?></label>
                    <input type="text" 
                           id="numero-certificado" 
                           name="numero_certificado" 
                           placeholder="<?php _e('Digite o número (ex: GC2024000001)', 'gestao-coletiva'); ?>" 
                           value="<?php echo esc_attr($numero_verificar); ?>"
                           required
                           style="width: 100%; padding: 12px; font-size: 16px; border: 2px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="gc-botao-verificar">
                    <button type="submit" class="gc-btn gc-btn-primary gc-btn-large">
                        🔍 <?php _e('Verificar Certificado', 'gestao-coletiva'); ?>
                    </button>
                </div>
            </form>
            
            <div id="gc-resultado-verificacao" style="margin-top: 20px;">
                <!-- Resultado da verificação será exibido aqui -->
            </div>
        </div>
    </div>
    
    <div class="gc-info-verificacao">
        <div class="gc-card">
            <h3>ℹ️ <?php _e('Como funciona a verificação?', 'gestao-coletiva'); ?></h3>
            <ul>
                <li><?php _e('Digite o número do certificado exatamente como aparece no documento', 'gestao-coletiva'); ?></li>
                <li><?php _e('O sistema verificará se o certificado é válido e autêntico', 'gestao-coletiva'); ?></li>
                <li><?php _e('Serão exibidas as informações da doação correspondente', 'gestao-coletiva'); ?></li>
                <li><?php _e('Certificados falsos ou alterados não passarão na verificação', 'gestao-coletiva'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-verificar se há número na URL
    <?php if (!empty($numero_verificar)): ?>
    setTimeout(function() {
        $('#gc-form-verificar').submit();
    }, 500);
    <?php endif; ?>
    
    // Handler do formulário de verificação
    $('#gc-form-verificar').on('submit', function(e) {
        e.preventDefault();
        
        var numero = $('#numero-certificado').val().trim();
        if (!numero) {
            alert('Por favor, digite o número do certificado');
            return;
        }
        
        // Limpar e formatar número
        numero = numero.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        
        if (numero.length < 10) {
            alert('Número do certificado inválido. Use o formato GC2024000001');
            return;
        }
        
        gc_verificar_certificado(numero);
    });
    
    function gc_verificar_certificado(numero) {
        var container = $('#gc-resultado-verificacao');
        var submitBtn = $('#gc-form-verificar button[type="submit"]');
        var originalText = submitBtn.text();
        
        // Mostrar loading
        container.html(`
            <div style="text-align: center; padding: 30px; border: 2px solid #0073aa; border-radius: 8px; background: #f8f9fa;">
                <div style="font-size: 24px; margin-bottom: 10px;">🔄</div>
                <p style="margin: 0; color: #0073aa; font-weight: 600;">Verificando certificado...</p>
                <small style="color: #666;">Aguarde enquanto validamos o certificado #${numero}</small>
            </div>
        `);
        
        submitBtn.prop('disabled', true).text('🔄 Verificando...');
        
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_verificar_certificado',
                numero_unico: numero,
                nonce: typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    gc_exibir_resultado_verificacao(response.data, true);
                } else {
                    gc_exibir_resultado_verificacao(response.data || 'Certificado não encontrado', false);
                }
            },
            error: function() {
                gc_exibir_resultado_verificacao('Erro de conexão. Tente novamente.', false);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function gc_exibir_resultado_verificacao(dados, sucesso) {
        var container = $('#gc-resultado-verificacao');
        
        if (sucesso) {
            var textoRecorrencia = '';
            if (dados.recorrencia && dados.recorrencia !== 'pontual') {
                var tipoRecorrencia = dados.recorrencia === 'mensal' ? 'Doação Mensal Recorrente' : 
                                    dados.recorrencia === 'anual' ? 'Doação Anual Recorrente' : 
                                    'Doação ' + dados.recorrencia.charAt(0).toUpperCase() + dados.recorrencia.slice(1);
                var statusRecorrencia = dados.recorrencia_ativa == 1 ? 'Ativa' : 'Inativa';
                textoRecorrencia = `
                    <div style="margin: 10px 0; padding: 8px; background: #e8f4fd; border-left: 4px solid #0073aa; border-radius: 4px;">
                        <strong>🔄 Recorrência:</strong> ${tipoRecorrencia} (${statusRecorrencia})
                    </div>
                `;
            }
            
            container.html(`
                <div style="border: 2px solid #28a745; border-radius: 8px; background: #f8fff9; padding: 25px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="font-size: 48px; color: #28a745; margin-bottom: 10px;">✅</div>
                        <h3 style="color: #28a745; margin: 0;">Certificado Válido e Autêntico</h3>
                        <p style="color: #666; margin: 5px 0 0 0;">Este certificado foi verificado com sucesso</p>
                    </div>
                    
                    <div style="background: white; padding: 20px; border-radius: 6px; border: 1px solid #e9ecef;">
                        <h4 style="margin: 0 0 15px 0; color: #333;">📋 Informações do Certificado</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <strong>👤 Doador:</strong><br>
                                <span style="font-size: 16px;">${dados.autor}</span>
                            </div>
                            <div>
                                <strong>💰 Valor:</strong><br>
                                <span style="font-size: 16px; color: #28a745; font-weight: 600;">
                                    R$ ${parseFloat(dados.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                </span>
                            </div>
                        </div>
                        
                        ${textoRecorrencia}
                        
                        <div style="margin: 10px 0;">
                            <strong>📄 Descrição:</strong><br>
                            <span>${dados.descricao_curta}</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: #666; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <div><strong>📅 Data da Doação:</strong><br>${gc_formatarDataVerificacao(dados.data_efetivacao)}</div>
                            <div><strong>🏷️ Número:</strong><br>#${dados.numero_unico}</div>
                        </div>
                    </div>
                </div>
            `);
        } else {
            container.html(`
                <div style="border: 2px solid #dc3545; border-radius: 8px; background: #fff8f8; padding: 25px; text-align: center;">
                    <div style="font-size: 48px; color: #dc3545; margin-bottom: 10px;">❌</div>
                    <h3 style="color: #dc3545; margin: 0 0 10px 0;">Certificado Inválido</h3>
                    <p style="color: #666; margin: 0;">${dados}</p>
                    <small style="color: #999; display: block; margin-top: 10px;">
                        Verifique se o número foi digitado corretamente ou entre em contato conosco.
                    </small>
                </div>
            `);
        }
    }
    
    function gc_formatarDataVerificacao(dataString) {
        var data = new Date(dataString.replace(' ', 'T'));
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});
</script>