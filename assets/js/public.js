/* Gestão Coletiva - Public JavaScript */

// Função para converter estados em texto amigável
function gc_estado_para_texto_js(estado) {
    var mapeamento = {
        'previsto': 'Previsto',
        'efetivado': 'Efetivado',
        'cancelado': 'Cancelado',
        'expirado': 'Expirado',
        'em_contestacao': 'Em Contestação',
        'contestado': 'Contestado',
        'confirmado': 'Confirmado',
        'aceito': 'Aceito',
        'em_disputa': 'Em Disputa',
        'retificado_comunidade': 'Confirmado pela Comunidade',
        'contestado_comunidade': 'Contestado pela Comunidade'
    };
    
    return mapeamento[estado] || estado.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

jQuery(document).ready(function($) {
    
    // Handlers globais para botões do painel
    $(document).on('click', '.gc-btn-acao, .gc-btn-contribuir', function() {
        var tipo = $(this).data('tipo');
        console.log('Public JS: Botão ação clicado:', tipo);
        gc_abrir_modal_lancamento_simples(tipo);
    });
    
    $(document).on('click', '.gc-btn-ver-lancamento', function() {
        console.log('Public JS: Botão consultar clicado');
        gc_abrir_modal_buscar_simples();
    });
    
    // Criar lançamento público
    $(document).on('submit', '#gc-form-lancamento-publico', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.text();
        
        submitBtn.prop('disabled', true).text('Enviando...');
        
        var formData = form.serialize();
        formData += '&action=gc_criar_lancamento';
        if (typeof gc_ajax !== 'undefined') {
            formData += '&nonce=' + gc_ajax.nonce;
        }
        
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    var lancamento = response.data;
                    var message = 'Lançamento criado com sucesso!\n\n';
                    message += 'Número: #' + lancamento.numero_unico + '\n';
                    message += 'Guarde este número para consultas futuras.';
                    alert(message);
                    
                    if (typeof gc_fechar_modal === 'function') {
                        gc_fechar_modal();
                    }
                    
                    // Exibir lançamento no modal em vez de redirecionar
                    if (lancamento.numero_unico) {
                        // Buscar e exibir o lançamento completo via AJAX
                        setTimeout(function() {
                            gc_buscar_lancamento_ajax(lancamento.numero_unico);
                        }, 1000);
                    }
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Erro ao processar solicitação. Tente novamente.');
                console.error('Ajax error:', error);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Buscar lançamento
    $(document).on('submit', '#gc-form-buscar, #gc-form-buscar-lancamento', function(e) {
        e.preventDefault();
        
        var numero = $(this).find('input[name="numero"], input[name="numero_lancamento"]').val();
        
        if (!numero) {
            alert('Por favor, digite o número do lançamento');
            return;
        }
        
        // Limpar e formatar número
        numero = numero.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        
        if (numero.length < 10) {
            alert('Número do lançamento inválido. Use o formato GC2024000001');
            return;
        }
        
        // Buscar via AJAX em vez de redirecionar
        gc_buscar_lancamento_ajax(numero);
    });
    
    // Contador de prazo em tempo real
    function gc_atualizar_contadores() {
        $('.gc-contador-prazo').each(function() {
            var $contador = $(this);
            var prazoString = $contador.data('prazo');
            
            if (!prazoString) return;
            
            var prazo = new Date(prazoString.replace(' ', 'T')).getTime();
            var agora = new Date().getTime();
            var diferenca = prazo - agora;
            
            var $tempoRestante = $contador.find('.gc-tempo-restante');
            
            if (diferenca > 0) {
                var dias = Math.floor(diferenca / (1000 * 60 * 60 * 24));
                var horas = Math.floor((diferenca % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutos = Math.floor((diferenca % (1000 * 60 * 60)) / (1000 * 60));
                
                var texto = '';
                if (dias > 0) {
                    texto += dias + ' dia' + (dias > 1 ? 's' : '') + ' ';
                }
                if (horas > 0 || dias > 0) {
                    texto += horas + 'h ';
                }
                texto += minutos + 'min';
                
                $tempoRestante.text(texto).removeClass('gc-vencido');
            } else {
                $tempoRestante.text('Vencido').addClass('gc-vencido');
            }
        });
    }
    
    // Inicializar contadores se existirem
    if ($('.gc-contador-prazo').length > 0) {
        gc_atualizar_contadores();
        setInterval(gc_atualizar_contadores, 60000); // Atualizar a cada minuto
    }
    
    // Gerar certificado
    $(document).on('click', '.gc-gerar-certificado', function() {
        var button = $(this);
        var lancamentoId = button.data('id');
        var originalText = button.text();
        
        button.prop('disabled', true).text('Gerando...');
        
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_gerar_certificado',
                id: lancamentoId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    gc_exibir_certificado(response.data);
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Erro ao gerar certificado');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Ver anexos
    $(document).on('click', '.gc-ver-anexos', function() {
        var anexos = $(this).data('anexos');
        if (anexos && anexos.length > 0) {
            if (anexos.length === 1) {
                // Se há apenas um anexo, abrir diretamente
                window.open(anexos[0], '_blank');
            } else {
                // Se há múltiplos anexos, mostrar modal com lista
                gc_mostrar_lista_anexos(anexos);
            }
        } else {
            alert('Nenhum anexo disponível.');
        }
    });
    
    // Abrir contestação
    $(document).on('click', '.gc-abrir-contestacao', function() {
        var lancamentoId = $(this).data('id');
        console.log('Botão contestar clicado, lançamento:', lancamentoId);
        console.log('gc_ajax disponível:', typeof gc_ajax !== 'undefined');
        
        // Verificar se o usuário está logado
        if (typeof gc_ajax === 'undefined') {
            alert('Você precisa estar logado para criar uma contestação. Faça login e tente novamente.');
            return;
        }
        
        console.log('Abrindo modal de contestação...');
        gc_abrir_modal_contestacao(lancamentoId);
    });
    
    // Períodos rápidos no livro caixa
    $('.gc-periodo-rapido').on('click', function() {
        var periodo = $(this).data('periodo');
        var hoje = new Date();
        var inicio, fim;
        
        switch(periodo) {
            case 'mes-atual':
                inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
                break;
            case 'mes-anterior':
                inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
                break;
            case 'ano-atual':
                inicio = new Date(hoje.getFullYear(), 0, 1);
                fim = new Date(hoje.getFullYear(), 11, 31);
                break;
            case 'tudo':
                inicio = new Date(2020, 0, 1);
                fim = hoje;
                break;
        }
        
        if (inicio && fim) {
            $('#data_inicio').val(inicio.toISOString().split('T')[0]);
            $('#data_fim').val(fim.toISOString().split('T')[0]);
            $('.gc-form-periodo').submit();
        }
    });
    
    // Ver lançamento da tabela
    $(document).on('click', '.gc-ver-lancamento', function() {
        var numero = $(this).data('numero');
        if (numero) {
            // Em vez de redirecionar, buscar via AJAX e mostrar no modal
            gc_buscar_lancamento_ajax(numero);
        }
    });
    
    // Funções auxiliares
    window.gc_buscar_lancamento_ajax = function(numero) {
        var container = $('#gc-resultado-busca');
        
        // Se não encontrar o container, criar um
        if (container.length === 0) {
            container = $('<div id="gc-resultado-busca"></div>');
            // Verificar se estamos em um modal
            var modal = $('.gc-modal-content:visible');
            if (modal.length > 0) {
                modal.find('#gc-form-buscar-lancamento').after(container);
            }
        }
        
        container.html('<p style="text-align: center; padding: 20px;"><span style="display: inline-block; animation: spin 1s linear infinite;">🔄</span> Buscando lançamento...</p>');
        
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_buscar_lancamento',
                numero: numero,
                nonce: typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Fechar o modal atual e abrir um novo com os detalhes do lançamento
                    gc_fechar_modal();
                    gc_exibir_lancamento_completo(response.data);
                } else {
                    container.html(`
                        <div style="text-align: center; padding: 20px; border: 1px solid #e74c3c; background: #fdf2f2; border-radius: 4px; margin: 10px 0;">
                            <h4 style="color: #e74c3c; margin: 0 0 10px 0;">❌ Lançamento não encontrado</h4>
                            <p style="margin: 0; color: #666;">Verifique se o número <strong>${numero}</strong> foi digitado corretamente.</p>
                            <small style="color: #999; display: block; margin-top: 8px;">Formato esperado: GC2024000001</small>
                        </div>
                    `);
                }
            },
            error: function() {
                container.html(`
                    <div style="text-align: center; padding: 20px; border: 1px solid #f39c12; background: #fef9e7; border-radius: 4px; margin: 10px 0;">
                        <h4 style="color: #f39c12; margin: 0 0 10px 0;">⚠️ Erro de conexão</h4>
                        <p style="margin: 0; color: #666;">Não foi possível buscar o lançamento. Tente novamente.</p>
                    </div>
                `);
            }
        });
    }
    
    window.gc_exibir_lancamento_completo = function(lancamento) {
        var html = `
        <div class="gc-lancamento-detalhes-modal">
            <div class="gc-lancamento-header" style="text-align: center; padding: 20px; border-bottom: 2px solid #0073aa;">
                <h2 style="margin: 0; color: #0073aa;">🏷️ Lançamento #${lancamento.numero_unico}</h2>
                <span class="gc-badge gc-estado-${lancamento.estado}" style="display: inline-block; margin-top: 10px; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    ${gc_estado_para_texto_js(lancamento.estado)}
                </span>
            </div>
            
            <div class="gc-lancamento-info" style="padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <strong>📝 Tipo:</strong><br>
                        <span class="gc-tipo-${lancamento.tipo}" style="color: ${lancamento.tipo === 'receita' ? '#28a745' : '#dc3545'}; font-weight: 600;">
                            ${lancamento.tipo === 'receita' ? '💰 Doação/Receita' : '💸 Despesa/Gasto'}
                        </span>
                    </div>
                    <div>
                        <strong>💵 Valor:</strong><br>
                        <span style="font-size: 18px; font-weight: bold; color: ${lancamento.tipo === 'receita' ? '#28a745' : '#dc3545'};">
                            R$ ${parseFloat(lancamento.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                        </span>
                    </div>
                </div>
                
                ${lancamento.recorrencia && lancamento.recorrencia !== 'pontual' ? `
                <div style="margin-bottom: 15px; padding: 10px; background: #e8f4fd; border-left: 4px solid #0073aa; border-radius: 4px;">
                    <strong>🔄 Recorrência:</strong><br>
                    <span style="color: #0073aa; font-weight: 600;">
                        ${lancamento.recorrencia === 'mensal' ? 'Doação Mensal Recorrente' : 
                          lancamento.recorrencia === 'anual' ? 'Doação Anual Recorrente' : 
                          'Doação ' + lancamento.recorrencia.charAt(0).toUpperCase() + lancamento.recorrencia.slice(1)}
                    </span>
                    ${lancamento.recorrencia_ativa == 1 ? '<span style="color: #28a745; margin-left: 10px;">✅ Ativa</span>' : '<span style="color: #dc3545; margin-left: 10px;">❌ Inativa</span>'}
                </div>
                ` : ''}
                
                <div style="margin-bottom: 15px;">
                    <strong>📄 Descrição:</strong><br>
                    <span>${lancamento.descricao_curta}</span>
                </div>
                
                ${lancamento.descricao_detalhada ? `
                <div style="margin-bottom: 15px;">
                    <strong>📋 Detalhes:</strong><br>
                    <span style="color: #666;">${lancamento.descricao_detalhada}</span>
                </div>
                ` : ''}
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: #666;">
                    <div><strong>📅 Criado em:</strong><br>${gc_formatarData(lancamento.data_criacao)}</div>
                    ${lancamento.data_efetivacao ? `<div><strong>✅ Efetivado em:</strong><br>${gc_formatarData(lancamento.data_efetivacao)}</div>` : ''}
                </div>
            </div>
            
            <div class="gc-acoes-lancamento" style="padding: 0 20px 20px; text-align: center;">
                ${lancamento.pode_gerar_certificado ? `
                <button type="button" class="gc-btn gc-btn-primary gc-gerar-certificado" data-id="${lancamento.id}">
                    🏆 Gerar Certificado
                </button>
                ` : ''}
                
                ${lancamento.pode_contestar ? `
                <button type="button" class="gc-btn gc-btn-outline gc-abrir-contestacao" data-id="${lancamento.id}">
                    ⚠️ Contestar
                </button>
                ` : ''}
                
                ${lancamento.anexos && lancamento.anexos.length > 0 ? `
                <button type="button" class="gc-btn gc-btn-outline gc-ver-anexos" data-anexos='${JSON.stringify(lancamento.anexos)}'>
                    📎 Ver Anexos (${lancamento.anexos.length})
                </button>
                ` : ''}
            </div>
        </div>
        `;
        
        gc_abrir_modal(html);
    }
    
    function gc_abrir_modal_contestacao(lancamentoId) {
        // Primeiro, buscar informações do lançamento para determinar o tipo
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_obter_tipo_lancamento',
                id: lancamentoId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    gc_criar_modal_contestacao_tipado(lancamentoId, response.data.tipo);
                } else {
                    alert('Erro ao obter informações do lançamento: ' + response.data);
                }
            },
            error: function() {
                alert('Erro de conexão. Tente novamente.');
            }
        });
    }
    
    function gc_criar_modal_contestacao_tipado(lancamentoId, tipoLancamento) {
        var html = '<div class="gc-modal-contestacao">';
        html += '<h3>Abrir Contestação</h3>';
        html += '<form id="form-contestacao">';
        html += '<p><label>Motivo da Contestação:</label>';
        html += '<select name="tipo" required>';
        html += '<option value="">Selecione o motivo...</option>';
        
        if (tipoLancamento === 'receita') {
            html += '<option value="doacao_nao_contabilizada">Doação não foi contabilizada</option>';
            html += '<option value="valor_incorreto_receita">Valor registrado está incorreto</option>';
            html += '<option value="data_incorreta_receita">Data registrada está incorreta</option>';
            html += '<option value="doacao_inexistente">Doação registrada não existe</option>';
            html += '<option value="doador_incorreto">Informações do doador incorretas</option>';
        } else if (tipoLancamento === 'despesa') {
            html += '<option value="despesa_nao_verificada">Despesa não pôde ser verificada</option>';
            html += '<option value="valor_incorreto_despesa">Valor registrado está incorreto</option>';
            html += '<option value="finalidade_questionavel">Finalidade da despesa é questionável</option>';
            html += '<option value="documentacao_insuficiente">Documentação insuficiente</option>';
            html += '<option value="despesa_desnecessaria">Despesa desnecessária ou inadequada</option>';
        }
        
        html += '</select></p>';
        html += '<p><label>Descrição:</label>';
        html += '<textarea name="descricao" rows="5" style="width: 100%;" required placeholder="Descreva detalhadamente a contestação..."></textarea></p>';
        html += '<p><label>Comprovante (opcional):</label>';
        html += '<input type="file" name="comprovante" accept=".pdf,.jpg,.jpeg,.png"></p>';
        html += '<p><button type="submit" class="gc-btn gc-btn-primary">Enviar Contestação</button>';
        html += ' <button type="button" class="gc-btn gc-btn-outline gc-fechar-modal">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-contestacao').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'gc_criar_contestacao');
            formData.append('lancamento_id', lancamentoId);
            if (typeof gc_ajax !== 'undefined') {
                formData.append('nonce', gc_ajax.nonce);
            }
            
            var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                    }
                    gc_fechar_modal();
                },
                error: function(xhr, status, error) {
                    alert('Erro ao criar contestação. Verifique se você está logado.');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    function gc_exibir_certificado(certificado) {
        var html = '<div class="gc-certificado" id="certificado-para-impressao">';
        html += '<div class="gc-certificado-header">';
        if (certificado.logo_url) {
            html += '<img src="' + certificado.logo_url + '" alt="Logo da Organização" class="gc-certificado-logo">';
        }
        html += '<h1>🏆 Certificado de Doação</h1>';
        html += '<h2>' + certificado.organizacao + '</h2>';
        html += '</div>';
        
        html += '<div class="gc-certificado-content">';
        html += '<div class="gc-certificado-info">';
        html += '<div class="gc-info-principal">';
        html += '<p class="gc-doador"><strong>Doador:</strong> ' + certificado.autor + '</p>';
        html += '<p class="gc-valor"><strong>Valor:</strong> R$ ' + parseFloat(certificado.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '</p>';
        
        if (certificado.recorrencia && certificado.recorrencia !== 'pontual') {
            var textoRecorrencia = certificado.recorrencia === 'mensal' ? 'Doação Mensal Recorrente' : 
                                 certificado.recorrencia === 'anual' ? 'Doação Anual Recorrente' : 
                                 'Doação ' + certificado.recorrencia.charAt(0).toUpperCase() + certificado.recorrencia.slice(1);
            var statusRecorrencia = certificado.recorrencia_ativa == 1 ? 'Ativa' : 'Inativa';
            html += '<p class="gc-recorrencia"><strong>Recorrência:</strong> ' + textoRecorrencia + ' (' + statusRecorrencia + ')</p>';
        }
        
        html += '<p class="gc-descricao"><strong>Descrição:</strong> ' + certificado.descricao_curta + '</p>';
        if (certificado.descricao_detalhada) {
            html += '<p class="gc-detalhes"><strong>Detalhes:</strong> ' + certificado.descricao_detalhada + '</p>';
        }
        html += '</div>';
        
        html += '<div class="gc-info-meta">';
        html += '<p><strong>Número do Certificado:</strong> #' + certificado.numero_unico + '</p>';
        html += '<p><strong>Data da Doação:</strong> ' + gc_formatarData(certificado.data_efetivacao) + '</p>';
        html += '<p><strong>Tipo:</strong> ' + (certificado.tipo === 'receita' ? 'Receita/Doação' : 'Despesa') + '</p>';
        html += '</div>';
        html += '</div>';
        
        if (certificado.qr_code_url) {
            html += '<div class="gc-qr-section">';
            html += '<div class="gc-qr-code">';
            html += '<img src="' + certificado.qr_code_url + '" alt="QR Code para verificação" class="gc-qr-image">';
            html += '<p class="gc-qr-texto">Escaneie para verificar a autenticidade</p>';
            html += '</div>';
            html += '</div>';
        }
        
        if (certificado.texto_agradecimento) {
            html += '<div class="gc-agradecimento">';
            html += '<p>' + certificado.texto_agradecimento + '</p>';
            html += '</div>';
        }
        
        html += '<div class="gc-verificacao">';
        html += '<p><small>Verificar autenticidade em: ' + certificado.site_url + '</small></p>';
        html += '<p><small>Emitido em: ' + gc_formatarData(new Date().toISOString()) + '</small></p>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="gc-certificado-actions no-print">';
        html += '<button type="button" class="gc-btn gc-btn-primary" onclick="gc_imprimirCertificado()">🖨️ Imprimir Certificado</button>';
        html += ' <button type="button" class="gc-btn" onclick="gc_baixarCertificado(\'' + certificado.numero_unico + '\')">📥 Baixar PDF</button>';
        html += ' <button type="button" class="gc-btn gc-btn-outline gc-fechar-modal">❌ Fechar</button>';
        html += '</div>';
        html += '</div>';
        
        gc_abrir_modal(html);
    }
    
    // Função para imprimir certificado
    window.gc_imprimirCertificado = function() {
        // Buscar o conteúdo do certificado no modal
        var certificadoContent = document.querySelector('.gc-certificado');
        
        if (!certificadoContent) {
            alert('Erro: Certificado não encontrado para impressão.');
            return;
        }
        
        // Clonar o conteúdo do certificado
        var certificadoClone = certificadoContent.cloneNode(true);
        
        // Remover botões de ação do clone
        var acoes = certificadoClone.querySelector('.gc-certificado-actions');
        if (acoes) {
            acoes.remove();
        }
        
        // Abrir nova janela para impressão
        var printWindow = window.open('', '_blank');
        
        // HTML completo da página de impressão
        var htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Certificado de Doação</title>
            <style>
                @page {
                    size: A4;
                    margin: 15mm;
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.4;
                    color: #333;
                    background: white;
                }
                .gc-certificado {
                    max-width: 100%;
                    margin: 0 auto;
                    border: 3px solid #0073aa;
                    padding: 30px;
                    background: white;
                    min-height: 90vh;
                    display: flex;
                    flex-direction: column;
                }
                .gc-certificado-header {
                    text-align: center;
                    margin-bottom: 25px;
                    border-bottom: 2px solid #0073aa;
                    padding-bottom: 20px;
                }
                .gc-certificado-header h1 {
                    color: #0073aa;
                    font-size: 28px;
                    margin-bottom: 8px;
                    font-weight: bold;
                }
                .gc-certificado-header h2 {
                    color: #666;
                    font-size: 18px;
                    font-weight: normal;
                }
                .gc-certificado-logo {
                    max-height: 80px;
                    margin-bottom: 15px;
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                }
                .gc-certificado-content {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }
                .gc-certificado-info {
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    margin-bottom: 20px;
                }
                .gc-info-principal {
                    flex: 2;
                }
                .gc-info-meta {
                    flex: 1;
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 6px;
                    border-left: 4px solid #0073aa;
                }
                .gc-doador, .gc-valor {
                    font-size: 20px;
                    margin: 12px 0;
                    font-weight: 600;
                }
                .gc-valor {
                    color: #28a745;
                }
                .gc-recorrencia {
                    font-size: 16px;
                    margin: 10px 0;
                    padding: 8px;
                    background: #e8f4fd;
                    border-left: 4px solid #0073aa;
                    border-radius: 4px;
                }
                .gc-qr-section {
                    text-align: center;
                    margin: 25px 0;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 6px;
                    border: 1px solid #e9ecef;
                }
                .gc-qr-image {
                    border: 2px solid #ddd;
                    padding: 8px;
                    border-radius: 4px;
                    background: white;
                    max-width: 120px;
                    height: auto;
                }
                .gc-qr-texto {
                    margin-top: 8px;
                    font-size: 12px;
                    color: #666;
                    font-style: italic;
                }
                .gc-agradecimento {
                    background: #e6f3ff;
                    padding: 20px;
                    border-left: 4px solid #0073aa;
                    border-radius: 0 4px 4px 0;
                    margin: 20px 0;
                    font-style: italic;
                    line-height: 1.6;
                }
                .gc-verificacao {
                    border-top: 1px solid #e9ecef;
                    padding-top: 15px;
                    margin-top: auto;
                    text-align: center;
                    color: #6c757d;
                    font-size: 11px;
                    line-height: 1.4;
                }
                .no-print, .gc-certificado-actions {
                    display: none !important;
                }
                
                @media print {
                    body { margin: 0; }
                    .gc-certificado { 
                        border: 3px solid #0073aa !important;
                        min-height: auto;
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            ${certificadoClone.outerHTML}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 500);
                };
            </script>
        </body>
        </html>`;
        
        printWindow.document.write(htmlContent);
        printWindow.document.close();
    }
    
    // Função placeholder para baixar PDF (pode ser implementada futuramente)
    window.gc_baixarCertificado = function(numeroUnico) {
        alert('Funcionalidade de download em PDF será implementada em uma versão futura. Por enquanto, use a opção "Imprimir" e selecione "Salvar como PDF" no seu navegador.');
    }
    
    // Sistema de modal simples
    var $modal = null;
    
    window.gc_abrir_modal = function(content) {
        if ($modal) {
            gc_fechar_modal();
        }
        
        $modal = $('<div class="gc-modal">').css({
            display: 'flex',
            position: 'fixed',
            zIndex: 9999,
            left: 0,
            top: 0,
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(0,0,0,0.5)',
            alignItems: 'center',
            justifyContent: 'center'
        });
        
        var $content = $('<div class="gc-modal-content">').css({
            backgroundColor: '#fff',
            padding: '0',
            borderRadius: '8px',
            maxWidth: '90%',
            maxHeight: '90%',
            overflow: 'auto',
            position: 'relative',
            width: '600px'
        });
        
        var $header = $('<div>').css({
            padding: '20px 30px 0 30px',
            borderBottom: '1px solid #eee'
        });
        
        var $close = $('<span class="gc-modal-close">').html('&times;').css({
            position: 'absolute',
            right: '15px',
            top: '15px',
            fontSize: '28px',
            fontWeight: 'bold',
            cursor: 'pointer',
            color: '#aaa'
        });
        
        var $body = $('<div>').css({
            padding: '20px 30px 30px 30px'
        }).html(content);
        
        $content.append($close, $body);
        $modal.append($content);
        $('body').append($modal);
        
        // Fechar ao clicar fora ou no X
        $modal.on('click', function(e) {
            if (e.target === this) {
                gc_fechar_modal();
            }
        });
        
        $close.on('click', function() {
            gc_fechar_modal();
        });
        
        // Usar event delegation para botões fechar (funciona mesmo após mudanças no DOM)
        $(document).off('click.modal-fechar-public').on('click.modal-fechar-public', '.gc-fechar-modal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            gc_fechar_modal();
        });
    };
    
    window.gc_fechar_modal = function() {
        if ($modal) {
            $modal.remove();
            $modal = null;
        }
    };
    
    // Escape key para fechar modal
    $(document).on('keyup', function(e) {
        if (e.keyCode === 27 && $modal) { // ESC
            gc_fechar_modal();
        }
    });
    
    // Função para imprimir certificado
    window.gc_imprimirCertificado = function() {
        var certificadoContent = $('.gc-certificado').html();
        var printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Certificado de Doação</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 40px; }
                    .gc-certificado-header { text-align: center; margin-bottom: 30px; }
                    .gc-certificado-header h2 { color: #0073aa; font-size: 32px; margin: 0; }
                    .gc-cert-info p { margin: 15px 0; font-size: 16px; }
                    .gc-cert-info strong { display: inline-block; min-width: 120px; }
                    .gc-agradecimento { margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; font-style: italic; }
                    .gc-certificado-actions { display: none; }
                </style>
            </head>
            <body>
                <div class="gc-certificado">${certificadoContent}</div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    };
    
    // Função auxiliar para formatar data
    function gc_formatarData(dataString) {
        var data = new Date(dataString.replace(' ', 'T'));
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Funções modais simplificadas para o painel
    window.gc_abrir_modal_lancamento_simples = function(tipo) {
        var titulo = tipo === 'receita' ? 'Fazer Doação' : 'Registrar Despesa';
        
        var html = '<h2>' + titulo + '</h2>';
        html += '<form id="gc-form-lancamento-publico">';
        html += '<input type="hidden" name="tipo" value="' + tipo + '">';
        html += '<p><label>Descrição breve:<br>';
        html += '<input type="text" name="descricao_curta" required maxlength="255" style="width: 100%;"></label></p>';
        html += '<p><label>Descrição detalhada:<br>';
        html += '<textarea name="descricao_detalhada" rows="4" style="width: 100%;"></textarea></label></p>';
        html += '<p><label>Valor (R$):<br>';
        html += '<input type="number" name="valor" min="0.01" step="0.01" required class="regular-text" style="width: 200px;"></label></p>';
        
        if (tipo === 'receita') {
            // Buscar informações PIX via AJAX
            $.ajax({
                url: typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'gc_get_pix_info',
                    nonce: typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : ''
                },
                success: function(response) {
                    if (response.success && response.data.chave_pix) {
                        var pixHtml = '<div class="gc-instrucoes-pix">';
                        pixHtml += '<h4>Instruções para Doação via PIX</h4>';
                        pixHtml += '<p>Para confirmar sua doação, realize a transferência via PIX:</p>';
                        pixHtml += '<div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px; padding: 15px; margin: 10px 0;">';
                        pixHtml += '<p><strong>Chave PIX:</strong> <span style="font-family: monospace; background: #f1f1f1; padding: 2px 6px; border-radius: 3px; cursor: pointer;" onclick="navigator.clipboard.writeText(this.textContent); alert(\'Chave PIX copiada!\')">' + response.data.chave_pix + '</span></p>';
                        if (response.data.nome_beneficiario) {
                            pixHtml += '<p><strong>Beneficiário:</strong> ' + response.data.nome_beneficiario + '</p>';
                        }
                        pixHtml += '</div>';
                        pixHtml += '<p>Após fazer o PIX, seu lançamento ficará "Previsto" até ser confirmado.</p>';
                        pixHtml += '</div>';
                        
                        $('.gc-modal .gc-instrucoes-pix').remove();
                        $('.gc-modal form').append(pixHtml);
                    }
                }
            });
        }
        
        html += '<p><button type="submit" class="gc-btn gc-btn-primary">' + titulo + '</button></p>';
        html += '</form>';
        
        gc_abrir_modal(html);
    }
    
    window.gc_abrir_modal_buscar_simples = function() {
        var html = '<h2>Consultar Lançamento</h2>';
        html += '<form id="gc-form-buscar-lancamento">';
        html += '<p><label>Número do lançamento:<br>';
        html += '<input type="text" name="numero_lancamento" placeholder="GC2024000001" required style="width: 100%;"></label></p>';
        html += '<p><button type="submit" class="gc-btn gc-btn-primary">Consultar</button></p>';
        html += '</form>';
        html += '<div id="gc-resultado-busca"></div>';
        
        gc_abrir_modal(html);
    }
    
    // Função para mostrar lista de anexos
    function gc_mostrar_lista_anexos(anexos) {
        var html = '<div class="gc-modal-anexos">';
        html += '<h3>📎 Anexos do Lançamento</h3>';
        html += '<div class="gc-lista-anexos">';
        
        anexos.forEach(function(anexo, index) {
            var nomeArquivo = anexo.split('/').pop().split('?')[0] || 'Anexo ' + (index + 1);
            html += '<div class="gc-anexo-item">';
            html += '<span class="gc-anexo-nome">' + nomeArquivo + '</span>';
            html += '<a href="' + anexo + '" target="_blank" class="gc-btn gc-btn-small">Ver</a>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '<div class="gc-modal-acoes">';
        html += '<button type="button" class="gc-btn gc-btn-outline gc-fechar-modal">Fechar</button>';
        html += '</div>';
        html += '</div>';
        
        gc_abrir_modal(html);
    }
    
    // Cancelar recorrência
    $(document).on('click', '.gc-cancelar-recorrencia', function() {
        var button = $(this);
        var lancamentoId = button.data('id');
        
        if (!confirm('Tem certeza que deseja cancelar esta recorrência? Esta ação não pode ser desfeita.')) {
            return;
        }
        
        button.prop('disabled', true).text('Cancelando...');
        
        var ajaxUrl = typeof gc_ajax !== 'undefined' ? gc_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = typeof gc_ajax !== 'undefined' ? gc_ajax.nonce : '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'gc_cancelar_recorrencia',
                id: lancamentoId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Erro: ' + response.data);
                    button.prop('disabled', false).text('Cancelar Recorrência');
                }
            },
            error: function() {
                alert('Erro de conexão. Tente novamente.');
                button.prop('disabled', false).text('Cancelar Recorrência');
            }
        });
    });
});