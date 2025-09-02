/* Gestão Coletiva - Admin JavaScript */

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
        'contestado_comunidade': 'Contestado pela Comunidade',
        'pendente': 'Pendente',
        'respondida': 'Respondida',
        'aceita': 'Aceita',
        'rejeitada': 'Rejeitada',
        'votacao_aberta': 'Votação Aberta',
        'disputa_finalizada': 'Disputa Finalizada',
        'expirada': 'Expirada'
    };
    
    return mapeamento[estado] || estado.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

jQuery(document).ready(function($) {
    
    // Atualizar estado de lançamentos
    $(document).on('click', '.gc-atualizar-estado', function() {
        var button = $(this);
        var id = button.data('id');
        var estado = button.data('estado');
        var confirmText = 'Tem certeza que deseja alterar o estado deste lançamento?';
        
        if (confirm(confirmText)) {
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gc_atualizar_estado',
                    id: id,
                    estado: estado,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                    button.prop('disabled', false);
                }
            });
        }
    });
    
    // Botão Nova Contestação
    $('#btn-nova-contestacao').on('click', function(e) {
        e.preventDefault();
        gc_abrir_modal_nova_contestacao();
    });
    
    // Responder contestação
    $(document).on('click', '.gc-responder-contestacao', function() {
        var contestacaoId = $(this).data('id');
        gc_abrir_modal_resposta_contestacao(contestacaoId);
    });
    
    // Abrir contestação (na visualização de lançamento)
    $(document).on('click', '.gc-abrir-contestacao', function() {
        var lancamentoId = $(this).data('id');
        gc_abrir_modal_contestacao_admin(lancamentoId);
    });

    // Analisar contestação
    $(document).on('click', '.gc-analisar-contestacao', function() {
        var contestacaoId = $(this).data('id');
        gc_abrir_modal_analise_contestacao(contestacaoId);
    });
    
    // Finalizar disputa
    $(document).on('click', '.gc-finalizar-disputa', function() {
        var contestacaoId = $(this).data('id');
        gc_abrir_modal_finalizar_disputa(contestacaoId);
    });
    
    // Registrar resultado da votação
    $(document).on('click', '.gc-registrar-resultado', function() {
        var contestacaoId = $(this).data('id');
        gc_abrir_modal_registrar_resultado(contestacaoId);
    });
    
    // Ver contestação
    $(document).on('click', '.gc-ver-contestacao', function() {
        var contestacaoId = $(this).data('id');
        gc_carregar_detalhes_contestacao(contestacaoId);
    });
    
    // Gerar certificado
    $(document).on('click', '.gc-gerar-certificado', function() {
        var button = $(this);
        var lancamentoId = button.data('id');
        
        button.prop('disabled', true).text('Gerando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_gerar_certificado',
                id: lancamentoId,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    gc_exibir_certificado(response.data);
                } else {
                    alert('Erro: ' + response.data);
                }
                button.prop('disabled', false).text('Gerar Certificado');
            },
            error: function() {
                alert('Erro ao gerar certificado');
                button.prop('disabled', false).text('Gerar Certificado');
            }
        });
    });
    
    // Upload de relatório
    $('#btn-upload-relatorio').on('click', function() {
        gc_abrir_modal_upload_relatorio();
    });
    
    // Excluir relatório
    $(document).on('click', '.gc-excluir-relatorio', function() {
        if (confirm('Tem certeza que deseja excluir este relatório?')) {
            var relatorioId = $(this).data('id');
            // Implementar exclusão de relatório
            console.log('Excluir relatório:', relatorioId);
        }
    });
    
    // Gerar relatório de período
    $('#btn-gerar-relatorio').on('click', function() {
        var dataInicio = $('#data_inicio').val();
        var dataFim = $('#data_fim').val();
        
        if (!dataInicio || !dataFim) {
            alert('Por favor, selecione as datas inicial e final');
            return;
        }
        
        gc_gerar_relatorio_periodo(dataInicio, dataFim);
    });
    
    // Processar vencimentos manualmente (configurações)
    $('#btn-processar-vencimentos').on('click', function() {
        var button = $(this);
        
        if (confirm('Deseja executar o processamento de vencimentos agora?')) {
            button.prop('disabled', true).text('Processando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gc_processar_vencimentos_manual',
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                    } else {
                        alert('Erro: ' + response.data);
                    }
                    button.prop('disabled', false).text('Processar Vencimentos Agora');
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                    button.prop('disabled', false).text('Processar Vencimentos Agora');
                }
            });
        }
    });
    
    // Funções auxiliares
    function gc_abrir_modal_resposta_contestacao(contestacaoId) {
        var html = '<div class="gc-modal-resposta">';
        html += '<h3>Responder Contestação</h3>';
        html += '<form id="form-resposta-contestacao">';
        html += '<p><label>Resposta:</label>';
        html += '<textarea name="resposta" rows="5" style="width: 100%;" required></textarea></p>';
        html += '<p><label>Ação:</label>';
        html += '<select name="novo_estado" id="resposta-estado" required>';
        html += '<option value="">Selecione...</option>';
        html += '<option value="procedente">Contestação Procedente (lançamento será contestado)</option>';
        html += '<option value="improcedente">Contestação Improcedente (lançamento será confirmado)</option>';
        html += '</select></p>';
        
        html += '<div id="correcoes-container" style="display:none; border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9;">';
        html += '<h4>Correções (opcional)</h4>';
        html += '<p><label><input type="checkbox" name="corrigir_valor" id="corrigir-valor"> Corrigir valor</label>';
        html += '<br><input type="number" name="novo_valor" step="0.01" min="0" style="width: 150px; margin-left: 20px;" disabled></p>';
        html += '<p><label><input type="checkbox" name="corrigir_descricao" id="corrigir-descricao"> Corrigir descrição breve</label>';
        html += '<br><input type="text" name="nova_descricao_curta" style="width: 100%; margin-left: 20px;" disabled></p>';
        html += '<p><label><input type="checkbox" name="corrigir_detalhes" id="corrigir-detalhes"> Corrigir detalhes</label>';
        html += '<br><textarea name="nova_descricao_detalhada" rows="3" style="width: 100%; margin-left: 20px;" disabled></textarea></p>';
        html += '</div>';
        
        html += '<p><button type="submit" class="button button-primary">Enviar Resposta</button>';
        html += ' <button type="button" class="button gc-fechar-modal">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        // Handlers para campos de correção
        $('#resposta-estado').on('change', function() {
            var estado = $(this).val();
            if (estado === 'procedente') {
                $('#correcoes-container').show();
            } else {
                $('#correcoes-container').hide();
                // Reset checkboxes e campos
                $('#correcoes-container input[type="checkbox"]').prop('checked', false);
                $('#correcoes-container input, #correcoes-container textarea').prop('disabled', true).val('');
            }
        });
        
        $('#corrigir-valor').on('change', function() {
            $('input[name="novo_valor"]').prop('disabled', !$(this).is(':checked'));
            if (!$(this).is(':checked')) {
                $('input[name="novo_valor"]').val('');
            }
        });
        
        $('#corrigir-descricao').on('change', function() {
            $('input[name="nova_descricao_curta"]').prop('disabled', !$(this).is(':checked'));
            if (!$(this).is(':checked')) {
                $('input[name="nova_descricao_curta"]').val('');
            }
        });
        
        $('#corrigir-detalhes').on('change', function() {
            $('textarea[name="nova_descricao_detalhada"]').prop('disabled', !$(this).is(':checked'));
            if (!$(this).is(':checked')) {
                $('textarea[name="nova_descricao_detalhada"]').val('');
            }
        });
        
        $('#form-resposta-contestacao').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            formData += '&action=gc_responder_contestacao&contestacao_id=' + contestacaoId + '&nonce=' + gc_ajax.nonce;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                    }
                    gc_fechar_modal();
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    function gc_abrir_modal_analise_contestacao(contestacaoId) {
        var html = '<div class="gc-modal-analise">';
        html += '<h3>Analisar Contestação</h3>';
        html += '<p>Após analisar a resposta fornecida, você aceita ou rejeita a contestação?</p>';
        html += '<p><button type="button" class="button button-primary gc-aceitar-contestacao" data-id="' + contestacaoId + '">Aceitar Resposta</button>';
        html += ' <button type="button" class="button gc-rejeitar-contestacao" data-id="' + contestacaoId + '">Rejeitar Resposta (Abrir Disputa)</button>';
        html += ' <button type="button" class="button gc-fechar-modal">Cancelar</button></p>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('.gc-aceitar-contestacao').on('click', function() {
            gc_analisar_contestacao_ajax(contestacaoId, true);
        });
        
        $('.gc-rejeitar-contestacao').on('click', function() {
            gc_analisar_contestacao_ajax(contestacaoId, false);
        });
    }
    
    function gc_analisar_contestacao_ajax(contestacaoId, aceitar) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_analisar_contestacao',
                contestacao_id: contestacaoId,
                aceitar: aceitar,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Erro: ' + response.data);
                }
                gc_fechar_modal();
            },
            error: function() {
                alert('Erro ao processar solicitação');
                gc_fechar_modal();
            }
        });
    }
    
    function gc_abrir_modal_nova_contestacao() {
        var html = '<div class="gc-modal-nova-contestacao">';
        html += '<h3>Nova Contestação</h3>';
        html += '<form id="form-nova-contestacao">';
        html += '<p><label>Número do Lançamento:</label>';
        html += '<input type="text" name="numero_lancamento" required placeholder="Digite o número do lançamento" style="width: 100%;"></p>';
        html += '<p><label>Tipo de Contestação:</label>';
        html += '<select name="tipo" required style="width: 100%;">';
        html += '<option value="">Selecione...</option>';
        html += '<option value="doacao_nao_contabilizada">Doação não foi contabilizada</option>';
        html += '<option value="despesa_nao_verificada">Despesa não pôde ser verificada</option>';
        html += '</select></p>';
        html += '<p><label>Descrição:</label>';
        html += '<textarea name="descricao" rows="5" required placeholder="Descreva detalhadamente a contestação..." style="width: 100%;"></textarea></p>';
        html += '<p><label>Comprovante (opcional):</label>';
        html += '<input type="file" name="comprovante" accept=".pdf,.jpg,.jpeg,.png"></p>';
        html += '<p><button type="submit" class="button button-primary">Criar Contestação</button>';
        html += ' <button type="button" class="button" onclick="gc_fechar_modal()">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-nova-contestacao').on('submit', function(e) {
            e.preventDefault();
            
            // Primeiro, buscar o lançamento pelo número
            var numeroLancamento = $('input[name="numero_lancamento"]').val();
            
            $.ajax({
                url: gc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_buscar_lancamento',
                    numero: numeroLancamento,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Lançamento encontrado, criar contestação
                        var formData = new FormData($('#form-nova-contestacao')[0]);
                        formData.append('action', 'gc_criar_contestacao');
                        formData.append('lancamento_id', response.data.id);
                        formData.append('nonce', gc_ajax.nonce);
                        
                        $.ajax({
                            url: gc_ajax.ajax_url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(contestResponse) {
                                if (contestResponse.success) {
                                    alert(contestResponse.data.message);
                                    location.reload();
                                } else {
                                    alert('Erro: ' + contestResponse.data);
                                }
                                gc_fechar_modal();
                            },
                            error: function() {
                                alert('Erro ao criar contestação');
                                gc_fechar_modal();
                            }
                        });
                    } else {
                        alert('Lançamento não encontrado com o número: ' + numeroLancamento);
                    }
                },
                error: function() {
                    alert('Erro ao buscar lançamento');
                }
            });
        });
    }
    
    function gc_abrir_modal_contestacao_admin(lancamentoId) {
        // Buscar informações do lançamento para determinar o tipo
        $.ajax({
            url: gc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gc_obter_tipo_lancamento',
                id: lancamentoId,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    gc_criar_modal_contestacao_admin_tipado(lancamentoId, response.data.tipo);
                } else {
                    alert('Erro ao obter informações do lançamento: ' + response.data);
                }
            },
            error: function() {
                alert('Erro de conexão. Tente novamente.');
            }
        });
    }
    
    function gc_criar_modal_contestacao_admin_tipado(lancamentoId, tipoLancamento) {
        var html = '<div class="gc-modal-contestacao">';
        html += '<h3>Abrir Contestação</h3>';
        html += '<form id="form-contestacao-admin">';
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
        html += '<p><button type="submit" class="button button-primary">Enviar Contestação</button>';
        html += ' <button type="button" class="button" onclick="gc_fechar_modal()">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-contestacao-admin').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'gc_criar_contestacao');
            formData.append('lancamento_id', lancamentoId);
            formData.append('nonce', gc_ajax.nonce);
            
            $.ajax({
                url: gc_ajax.ajax_url,
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
                    alert('Erro ao criar contestação');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    function gc_abrir_modal_upload_relatorio() {
        var html = '<div class="gc-modal-upload">';
        html += '<h3>Incluir Relatório</h3>';
        html += '<form id="form-upload-relatorio" enctype="multipart/form-data">';
        html += '<p><label>Título:</label>';
        html += '<input type="text" name="titulo" required style="width: 100%;"></p>';
        html += '<p><label>Tipo:</label>';
        html += '<select name="tipo" required>';
        html += '<option value="">Selecione...</option>';
        html += '<option value="mensal">Mensal</option>';
        html += '<option value="trimestral">Trimestral</option>';
        html += '<option value="anual">Anual</option>';
        html += '</select></p>';
        html += '<p><label>Período:</label>';
        html += '<input type="text" name="periodo" placeholder="Ex: 2024-01, 2024-T1, 2024" required style="width: 100%;"></p>';
        html += '<p><label>Arquivo:</label>';
        html += '<input type="file" name="arquivo" accept=".pdf,.xlsx,.xls" required></p>';
        html += '<p><button type="submit" class="button button-primary">Enviar Relatório</button>';
        html += ' <button type="button" class="button gc-fechar-modal">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-upload-relatorio').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'gc_upload_relatorio');
            formData.append('nonce', gc_ajax.nonce);
            
            $.ajax({
                url: ajaxurl,
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
                error: function() {
                    alert('Erro ao fazer upload do relatório');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    // Tabs de relatórios
    $('.gc-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        // Atualizar botões
        $('.gc-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Atualizar conteúdo
        $('.gc-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Botão gerar previsão
    $('#btn-gerar-previsao').on('click', function() {
        var dataInicio = $('#data_inicio_prev').val();
        var dataFim = $('#data_fim_prev').val();
        
        if (!dataInicio || !dataFim) {
            alert('Por favor, selecione as datas de início e fim');
            return;
        }
        
        if (dataFim <= dataInicio) {
            alert('A data final deve ser posterior à data inicial');
            return;
        }
        
        gc_gerar_relatorio_previsao(dataInicio, dataFim);
    });
    
    // Presets de período
    $('.gc-preset-periodo').on('click', function() {
        var meses = parseInt($(this).data('meses'));
        var hoje = new Date();
        var dataInicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        var dataFim = new Date(hoje.getFullYear(), hoje.getMonth() + meses, 0);
        
        $('#data_inicio_prev').val(dataInicio.toISOString().split('T')[0]);
        $('#data_fim_prev').val(dataFim.toISOString().split('T')[0]);
    });
    
    // Sistema de tabs (delegação para conteúdo AJAX)
    $(document).on('click', '.gc-tab-btn[data-tab="previstos"], .gc-tab-btn[data-tab="realizados"]', function() {
        var tab = $(this).data('tab');
        var container = $(this).closest('.gc-lancamentos-previsao');
        
        console.log('GC Debug: Tab clicada:', tab);
        console.log('GC Debug: Container encontrado:', container.length);
        console.log('GC Debug: Botões no container:', container.find('.gc-tab-btn').length);
        console.log('GC Debug: Conteúdos no container:', container.find('.gc-tab-content').length);
        
        // Atualizar botões dentro do container
        container.find('.gc-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Atualizar conteúdo dentro do container
        container.find('.gc-tab-content').removeClass('active');
        var targetTab = container.find('#tab-' + tab);
        targetTab.addClass('active');
        
        console.log('GC Debug: Target tab encontrada:', targetTab.length);
        console.log('GC Debug: Target tab visível:', targetTab.is(':visible'));
    });
    
    function gc_gerar_relatorio_previsao(dataInicio, dataFim) {
        $('#relatorio-previsao').hide().html('<div class="notice notice-info"><p>🔄 Gerando relatório de previsão... Este processo pode demorar alguns segundos.</p></div>').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_gerar_relatorio_previsao',
                data_inicio: dataInicio,
                data_fim: dataFim,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#relatorio-previsao').html(response.data);
                } else {
                    $('#relatorio-previsao').html('<div class="notice notice-error"><p>Erro ao gerar relatório: ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $('#relatorio-previsao').html('<div class="notice notice-error"><p>Erro ao processar solicitação. Tente novamente.</p></div>');
            }
        });
    }
    
    function gc_gerar_relatorio_periodo(dataInicio, dataFim) {
        $('#relatorio-periodo').hide().html('<p>Carregando...</p>').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_gerar_relatorio_periodo',
                data_inicio: dataInicio,
                data_fim: dataFim,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#relatorio-periodo').html(response.data);
                } else {
                    $('#relatorio-periodo').html('<p>Erro ao gerar relatório: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#relatorio-periodo').html('<p>Erro ao processar solicitação</p>');
            }
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
        html += '<p class="gc-descricao"><strong>Descrição:</strong> ' + certificado.descricao_curta + '</p>';
        if (certificado.descricao_detalhada) {
            html += '<p class="gc-detalhes"><strong>Detalhes:</strong> ' + certificado.descricao_detalhada + '</p>';
        }
        html += '</div>';
        
        html += '<div class="gc-info-meta">';
        html += '<p><strong>Número do Certificado:</strong> #' + certificado.numero_unico + '</p>';
        html += '<p><strong>Data da Doação:</strong> ' + gc_formatarDataBrasil(certificado.data_efetivacao) + '</p>';
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
        html += '<p><small>Emitido em: ' + gc_formatarDataBrasil(new Date().toISOString()) + '</small></p>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="gc-certificado-actions no-print">';
        html += '<button type="button" class="button button-primary" onclick="gc_imprimirCertificado()">🖨️ Imprimir Certificado</button>';
        html += ' <button type="button" class="button" onclick="gc_baixarCertificado(\'' + certificado.numero_unico + '\')">📥 Baixar PDF</button>';
        html += ' <button type="button" class="button gc-fechar-modal">❌ Fechar</button>';
        html += '</div>';
        html += '</div>';
        
        gc_abrir_modal(html);
    }
    
    // Função para formatar data em português brasileiro
    function gc_formatarDataBrasil(dataStr) {
        if (!dataStr) return 'Data não disponível';
        var data = new Date(dataStr);
        if (isNaN(data.getTime())) return 'Data inválida';
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Função para imprimir certificado
    window.gc_imprimirCertificado = function() {
        // Abrir nova janela para impressão
        var printWindow = window.open('', '_blank');
        var certificadoContent = document.getElementById('certificado-para-impressao');
        
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
    
    function gc_abrir_modal(content) {
        if ($modal) {
            gc_fechar_modal();
        }
        
        $modal = $('<div class="gc-modal-overlay">').css({
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(0,0,0,0.5)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
        });
        
        var $content = $('<div class="gc-modal-content">').css({
            backgroundColor: '#fff',
            padding: '20px',
            borderRadius: '8px',
            maxWidth: '90vw',
            maxHeight: '90vh',
            overflow: 'auto',
            position: 'relative'
        }).html(content);
        
        $modal.append($content);
        $('body').append($modal);
        
        // Fechar ao clicar fora do conteúdo
        $modal.on('click', function(e) {
            if (e.target === this) {
                gc_fechar_modal();
            }
        });
        
        // Usar event delegation para botões fechar (funciona mesmo após mudanças no DOM)
        $(document).off('click.modal-fechar').on('click.modal-fechar', '.gc-fechar-modal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            gc_fechar_modal();
        });
    }
    
    function gc_fechar_modal() {
        if ($modal) {
            $modal.remove();
            $modal = null;
        }
    }
    
    // Escape key para fechar modal
    $(document).on('keyup', function(e) {
        if (e.keyCode === 27 && $modal) { // ESC
            gc_fechar_modal();
        }
    });
    
    
    // Limpar dados por período
    $('#btn-limpar-periodo').on('click', function() {
        var button = $(this);
        var dataInicial = $('#data-inicial-limpeza').val();
        var dataFinal = $('#data-final-limpeza').val();
        
        if (!dataInicial || !dataFinal) {
            alert('Por favor, selecione as datas inicial e final.');
            return;
        }
        
        if (!confirm('ATENÇÃO: Esta ação é IRREVERSÍVEL. Todos os lançamentos e contestações do período selecionado serão PERMANENTEMENTE removidos. Você tem certeza?')) {
            return;
        }
        
        if (!confirm('CONFIRMAÇÃO FINAL: Você realmente deseja excluir todos os dados do período ' + dataInicial + ' até ' + dataFinal + '? Esta operação NÃO PODE ser desfeita!')) {
            return;
        }
        
        button.prop('disabled', true).text('Limpando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_limpar_periodo',
                data_inicial: dataInicial,
                data_final: dataFinal,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#data-inicial-limpeza').val('');
                    $('#data-final-limpeza').val('');
                } else {
                    alert('Erro: ' + response.data);
                }
                button.prop('disabled', false).text('Limpar Lançamentos do Período');
            },
            error: function() {
                alert('Erro ao processar solicitação');
                button.prop('disabled', false).text('Limpar Lançamentos do Período');
            }
        });
    });
    
    // Habilitar/desabilitar botão de limpeza completa
    $('#confirmar-limpeza-total').on('change', function() {
        $('#btn-limpar-tudo').prop('disabled', !$(this).is(':checked'));
    });
    
    // Limpar todos os dados
    // Handler para cancelar recorrência
    $('.gc-cancelar-recorrencia').on('click', function() {
        var button = $(this);
        var lancamentoId = button.data('id');
        
        if (!confirm('Tem certeza que deseja cancelar esta recorrência? Esta ação não pode ser desfeita.')) {
            return;
        }
        
        button.prop('disabled', true).text('Cancelando...');
        
        $.ajax({
            url: gc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gc_cancelar_recorrencia',
                id: lancamentoId,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload(); // Recarregar página para atualizar informações
                } else {
                    alert('Erro: ' + response.data);
                    button.prop('disabled', false).text('Cancelar Recorrência');
                }
            },
            error: function() {
                alert('Erro ao processar solicitação');
                button.prop('disabled', false).text('Cancelar Recorrência');
            }
        });
    });
    
    // Handler para ver série de recorrência
    $('.gc-ver-serie-recorrencia').on('click', function() {
        var button = $(this);
        var lancamentoId = button.data('id');
        
        // Redirecionar para lista filtrada por série
        var url = 'admin.php?page=gc-lancamentos&serie=' + lancamentoId;
        window.location.href = url;
    });
    
    // Ver contestações de um lançamento
    $(document).on('click', '.gc-ver-contestacoes-lancamento', function() {
        var lancamentoId = $(this).data('id');
        gc_abrir_modal_lista_contestacoes(lancamentoId);
    });
    
    $('#btn-limpar-tudo').on('click', function() {
        var button = $(this);
        
        if (!$('#confirmar-limpeza-total').is(':checked')) {
            alert('Por favor, confirme que deseja apagar todos os dados marcando a caixa de seleção.');
            return;
        }
        
        if (!confirm('PERIGO: Esta ação removerá COMPLETAMENTE todas as tabelas e dados do plugin. Esta operação é IRREVERSÍVEL. Tem certeza absoluta?')) {
            return;
        }
        
        if (!confirm('ÚLTIMA CONFIRMAÇÃO: Todos os lançamentos, contestações e relatórios serão PERMANENTEMENTE apagados. Digite "SIM" na próxima caixa se realmente deseja prosseguir.')) {
            return;
        }
        
        var confirmacao = prompt('Digite "SIM" (em maiúsculas) para confirmar a remoção COMPLETA de todos os dados:');
        
        if (confirmacao !== 'SIM') {
            alert('Operação cancelada. Para prosseguir, é necessário digitar exatamente "SIM".');
            return;
        }
        
        button.prop('disabled', true).text('Apagando dados...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_limpar_tudo',
                confirmacao: 'confirmar',
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#confirmar-limpeza-total').prop('checked', false);
                } else {
                    alert('Erro: ' + response.data);
                }
                button.prop('disabled', true).text('🗑️ Apagar Todos os Dados');
            },
            error: function() {
                alert('Erro ao processar solicitação');
                button.prop('disabled', true).text('🗑️ Apagar Todos os Dados');
            }
        });
    });
    
    function gc_abrir_modal_finalizar_disputa(contestacaoId) {
        var html = '<div class="gc-modal-finalizar-disputa">';
        html += '<h3>Finalizar Disputa</h3>';
        html += '<p>Para finalizar esta disputa, é necessário fornecer os links para:</p>';
        html += '<form id="form-finalizar-disputa">';
        
        html += '<p>';
        html += '<label for="link-postagem"><strong>Link da Postagem no Blog:</strong></label><br>';
        html += '<input type="url" id="link-postagem" name="link_postagem" class="regular-text" required>';
        html += '<br><small>URL completa da postagem que detalha a disputa no blog.</small>';
        html += '</p>';
        
        html += '<p>';
        html += '<label for="link-votacao"><strong>Link do Formulário de Votação:</strong></label><br>';
        html += '<input type="url" id="link-votacao" name="link_votacao" class="regular-text" required>';
        html += '<br><small>URL do formulário onde a comunidade pode votar sobre a disputa.</small>';
        html += '</p>';
        
        html += '<p>';
        html += '<button type="submit" class="button button-primary">Finalizar Disputa</button> ';
        html += '<button type="button" class="button gc-fechar-modal">Cancelar</button>';
        html += '</p>';
        
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-finalizar-disputa').on('submit', function(e) {
            e.preventDefault();
            
            var linkPostagem = $('#link-postagem').val();
            var linkVotacao = $('#link-votacao').val();
            
            if (!linkPostagem || !linkVotacao) {
                alert('Ambos os links são obrigatórios.');
                return;
            }
            
            // Validação básica de URL
            var urlPattern = /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/;
            if (!urlPattern.test(linkPostagem)) {
                alert('O link da postagem deve ser uma URL válida.');
                return;
            }
            
            if (!urlPattern.test(linkVotacao)) {
                alert('O link do formulário deve ser uma URL válida.');
                return;
            }
            
            $('button[type="submit"]').prop('disabled', true).text('Finalizando...');
            
            $.ajax({
                url: gc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_finalizar_disputa',
                    contestacao_id: contestacaoId,
                    link_postagem: linkPostagem,
                    link_votacao: linkVotacao,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                        $('button[type="submit"]').prop('disabled', false).text('Finalizar Disputa');
                    }
                    gc_fechar_modal();
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                    $('button[type="submit"]').prop('disabled', false).text('Finalizar Disputa');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    function gc_abrir_modal_registrar_resultado(contestacaoId) {
        var html = '<div class="gc-modal-registrar-resultado">';
        html += '<h3>Registrar Resultado da Votação</h3>';
        html += '<p>Informe o resultado da votação comunitária para finalizar definitivamente esta disputa.</p>';
        html += '<form id="form-registrar-resultado">';
        
        html += '<p>';
        html += '<label><strong>Resultado da Votação:</strong></label><br>';
        html += '<label><input type="radio" name="resultado_votacao" value="contestacao_procedente" required> ';
        html += 'Contestação Procedente <small>(comunidade considera que há erro no lançamento)</small></label><br>';
        html += '<label><input type="radio" name="resultado_votacao" value="contestacao_improcedente" required> ';
        html += 'Contestação Improcedente <small>(comunidade considera que lançamento está correto)</small></label>';
        html += '</p>';
        
        html += '<p>';
        html += '<label for="observacoes"><strong>Observações (opcional):</strong></label><br>';
        html += '<textarea id="observacoes" name="observacoes" rows="4" cols="50" placeholder="Resumo dos votos, comentários relevantes, etc."></textarea>';
        html += '</p>';
        
        html += '<div style="border: 1px solid #ccc; padding: 10px; background: #f9f9f9; margin: 10px 0;">';
        html += '<strong>Resultado Final:</strong><br>';
        html += '<small>• Se <strong>Contestação Procedente</strong>: lançamento será marcado como "Retificado pela Comunidade"</small><br>';
        html += '<small>• Se <strong>Contestação Improcedente</strong>: lançamento será marcado como "Contestado pela Comunidade"</small>';
        html += '</div>';
        
        html += '<p>';
        html += '<button type="submit" class="button button-primary">Registrar Resultado Final</button> ';
        html += '<button type="button" class="button gc-fechar-modal">Cancelar</button>';
        html += '</p>';
        
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        $('#form-registrar-resultado').on('submit', function(e) {
            e.preventDefault();
            
            var resultadoVotacao = $('input[name="resultado_votacao"]:checked').val();
            var observacoes = $('#observacoes').val();
            
            if (!resultadoVotacao) {
                alert('Por favor, selecione o resultado da votação.');
                return;
            }
            
            if (!confirm('ATENÇÃO: Esta ação é DEFINITIVA e encerrará completamente a disputa. Tem certeza do resultado selecionado?')) {
                return;
            }
            
            $('button[type="submit"]').prop('disabled', true).text('Registrando...');
            
            $.ajax({
                url: gc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_registrar_resultado',
                    contestacao_id: contestacaoId,
                    resultado_votacao: resultadoVotacao,
                    observacoes: observacoes,
                    nonce: gc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                        $('button[type="submit"]').prop('disabled', false).text('Registrar Resultado Final');
                    }
                    gc_fechar_modal();
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                    $('button[type="submit"]').prop('disabled', false).text('Registrar Resultado Final');
                    gc_fechar_modal();
                }
            });
        });
    }
    
    function gc_carregar_detalhes_contestacao(contestacaoId) {
        $.ajax({
            url: gc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gc_ver_contestacao',
                contestacao_id: contestacaoId,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    gc_exibir_detalhes_contestacao(response.data);
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Erro ao carregar detalhes da contestação');
            }
        });
    }
    
    function gc_exibir_detalhes_contestacao(dados) {
        console.log('=== DEBUG CONTESTAÇÃO ===');
        console.log('Dados completos:', dados);
        console.log('Estado:', dados.contestacao.estado);
        console.log('Resultado votação:', dados.contestacao.resultado_votacao);
        console.log('Data resolução final:', dados.contestacao.data_resolucao_final);
        console.log('==========================');
        
        var contestacao = dados.contestacao;
        var lancamento = dados.lancamento;
        
        var html = '<div class="gc-modal-ver-contestacao">';
        html += '<h3>Detalhes da Contestação #' + contestacao.id + '</h3>';
        
        // Informações do lançamento
        html += '<div class="gc-section">';
        html += '<h4>📊 Lançamento Contestado</h4>';
        html += '<p><strong>Número:</strong> #' + lancamento.numero_unico + '</p>';
        html += '<p><strong>Tipo:</strong> ' + (lancamento.tipo === 'receita' ? 'Receita' : 'Despesa') + '</p>';
        html += '<p><strong>Descrição:</strong> ' + lancamento.descricao_curta + '</p>';
        html += '<p><strong>Valor:</strong> R$ ' + parseFloat(lancamento.valor).toFixed(2).replace('.', ',') + '</p>';
        html += '<p><strong>Estado Atual:</strong> <span class="gc-badge">' + gc_estado_para_texto_js(lancamento.estado) + '</span></p>';
        html += '<p><strong>Autor do Lançamento:</strong> ' + lancamento.autor_nome + '</p>';
        html += '</div>';
        
        // Informações da contestação
        html += '<div class="gc-section">';
        html += '<h4>⚖️ Detalhes da Contestação</h4>';
        html += '<p><strong>Tipo:</strong> ' + (contestacao.tipo === 'doacao_nao_contabilizada' ? 'Doação não contabilizada' : 'Despesa não verificada') + '</p>';
        
        var estado_display = contestacao.estado ? gc_estado_para_texto_js(contestacao.estado) : 'NÃO DEFINIDO';
        var estado_cor = contestacao.estado ? 'gc-contestacao-' + contestacao.estado : 'gc-contestacao-indefinido';
        
        html += '<p><strong>Estado:</strong> <span class="gc-badge ' + estado_cor + '">' + estado_display + '</span></p>';
        
        // Detectar possível inconsistência de estado
        if (!contestacao.estado && lancamento.estado === 'retificado_comunidade') {
            html += '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            html += '<strong>⚠️ Inconsistência Detectada:</strong> O lançamento indica resolução comunitária, mas o estado da contestação não foi registrado adequadamente. ';
            html += 'Isso pode ter ocorrido devido a uma atualização do sistema. O lançamento foi marcado como "RETIFICADO PELA COMUNIDADE".';
            html += '</div>';
        }
        
        // Se a disputa foi resolvida, mostrar resultado final
        if (contestacao.estado === 'disputa_finalizada' && contestacao.resultado_votacao) {
            var resultado_final = contestacao.resultado_votacao === 'contestacao_procedente' ? 
                '✅ Contestação foi considerada PROCEDENTE pela comunidade' : 
                '❌ Contestação foi considerada IMPROCEDENTE pela comunidade';
            html += '<div style="background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            html += '<strong>🏁 Resultado Final:</strong> ' + resultado_final;
            if (contestacao.observacoes_finais) {
                html += '<br><strong>Observações:</strong> ' + contestacao.observacoes_finais;
            }
            html += '</div>';
        } else if (contestacao.estado === 'votacao_aberta') {
            html += '<div style="background: #fff8e1; border: 1px solid #ff9800; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            html += '<strong>🗳️ Votação Aberta:</strong> Esta disputa foi publicada no blog e está aberta para votação da comunidade.';
            if (contestacao.data_finalizacao_disputa) {
                html += '<br><strong>Data de Publicação:</strong> ' + new Date(contestacao.data_finalizacao_disputa).toLocaleDateString('pt-BR');
            }
            html += '</div>';
        }
        
        html += '<p><strong>Autor da Contestação:</strong> ' + contestacao.autor_nome + '</p>';
        html += '<p><strong>Data da Contestação:</strong> ' + new Date(contestacao.data_criacao).toLocaleDateString('pt-BR') + ' ' + new Date(contestacao.data_criacao).toLocaleTimeString('pt-BR') + '</p>';
        html += '<p><strong>Descrição:</strong></p>';
        html += '<div style="background: #f5f5f5; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0;">' + contestacao.descricao + '</div>';
        
        if (contestacao.comprovante) {
            html += '<p><strong>Comprovante:</strong> ' + contestacao.comprovante + '</p>';
        }
        html += '</div>';
        
        // Timeline da contestação
        html += '<div class="gc-section">';
        html += '<h4>📅 Histórico</h4>';
        html += '<ul class="gc-timeline">';
        
        html += '<li>✅ <strong>Contestação criada</strong> - ' + new Date(contestacao.data_criacao).toLocaleDateString('pt-BR') + '</li>';
        
        if (contestacao.data_resposta) {
            html += '<li>📝 <strong>Resposta administrativa</strong> - ' + new Date(contestacao.data_resposta).toLocaleDateString('pt-BR');
            if (contestacao.resposta) {
                html += '<br><em>' + contestacao.resposta + '</em>';
            }
            html += '</li>';
        }
        
        if (contestacao.data_analise) {
            html += '<li>🔍 <strong>Análise do contestante</strong> - ' + new Date(contestacao.data_analise).toLocaleDateString('pt-BR') + '</li>';
        }
        
        if (contestacao.data_finalizacao_disputa) {
            html += '<li>🌐 <strong>Disputa publicada</strong> - ' + new Date(contestacao.data_finalizacao_disputa).toLocaleDateString('pt-BR') + '</li>';
            
            if (contestacao.link_postagem_blog) {
                html += '<li>📝 <strong>Post no blog:</strong> <a href="' + contestacao.link_postagem_blog + '" target="_blank">' + contestacao.link_postagem_blog + '</a></li>';
            }
            
            if (contestacao.link_formulario_votacao) {
                html += '<li>🗳️ <strong>Formulário de votação:</strong> <a href="' + contestacao.link_formulario_votacao + '" target="_blank">' + contestacao.link_formulario_votacao + '</a></li>';
            }
        }
        
        if (contestacao.data_resolucao_final || contestacao.estado === 'disputa_resolvida') {
            var data_resolucao = contestacao.data_resolucao_final ? 
                new Date(contestacao.data_resolucao_final).toLocaleDateString('pt-BR') : 
                'Data não registrada';
            html += '<li>🏁 <strong>Disputa resolvida definitivamente</strong> - ' + data_resolucao;
            
            if (contestacao.resultado_votacao) {
                var resultado_texto = contestacao.resultado_votacao === 'contestacao_procedente' ? 
                    '✅ Contestação Procedente (comunidade considerou que há erro no lançamento)' : 
                    '❌ Contestação Improcedente (comunidade considerou que lançamento está correto)';
                html += '<br><strong>🗳️ Resultado da Votação:</strong> ' + resultado_texto;
            } else {
                html += '<br><strong>⚠️</strong> Resultado da votação não registrado no sistema';
            }
            
            if (contestacao.observacoes_finais) {
                html += '<br><strong>📝 Observações:</strong> <em>' + contestacao.observacoes_finais + '</em>';
            }
            html += '</li>';
        }
        
        html += '</ul>';
        html += '</div>';
        
        // Ações disponíveis
        html += '<div class="gc-section">';
        html += '<h4>🔧 Ações Disponíveis</h4>';
        
        var temAcoes = false;
        
        if (dados.pode_responder && contestacao.estado === 'pendente') {
            html += '<p><button type="button" class="button button-primary gc-responder-contestacao-modal" data-id="' + contestacao.id + '">Responder Contestação</button></p>';
            temAcoes = true;
        }
        
        if (dados.pode_analisar && contestacao.estado === 'respondida') {
            html += '<p><button type="button" class="button button-primary gc-analisar-contestacao-modal" data-id="' + contestacao.id + '">Analisar Resposta</button></p>';
            temAcoes = true;
        }
        
        if (dados.pode_finalizar && contestacao.estado === 'em_disputa') {
            console.log('Adicionando botão Finalizar Disputa - Estado:', contestacao.estado, 'Pode finalizar:', dados.pode_finalizar);
            html += '<p><button type="button" class="button button-primary gc-finalizar-disputa-modal" data-id="' + contestacao.id + '">📝 Registrar Links (Blog + Votação)</button></p>';
            html += '<p class="description">Registre os links da postagem no blog e do formulário de votação para finalizar a disputa.</p>';
            temAcoes = true;
        }
        
        if (dados.pode_registrar_resultado && contestacao.estado === 'votacao_aberta') {
            html += '<p><button type="button" class="button button-primary gc-registrar-resultado-modal" data-id="' + contestacao.id + '">🗳️ Registrar Resultado da Votação</button></p>';
            html += '<p class="description">Informe o resultado da votação comunitária para encerrar definitivamente a disputa.</p>';
            temAcoes = true;
        }
        
        if (contestacao.estado === 'votacao_aberta') {
            html += '<div style="background: #fff8e1; border: 1px solid #ff9800; padding: 15px; border-radius: 4px;">';
            html += '<h4 style="margin-top: 0;">🗳️ Votação em Andamento</h4>';
            html += '<p>A disputa foi publicada e está aberta para votação da comunidade. ';
            html += 'Use o botão acima para registrar o resultado quando a votação for concluída.</p>';
            if (contestacao.link_postagem_blog) {
                html += '<p><a href="' + contestacao.link_postagem_blog + '" target="_blank" class="button button-small">📝 Ver Post no Blog</a> ';
            }
            if (contestacao.link_formulario_votacao) {
                html += '<a href="' + contestacao.link_formulario_votacao + '" target="_blank" class="button button-small">🗳️ Ver Formulário de Votação</a></p>';
            }
            html += '</div>';
        } else if (contestacao.estado === 'disputa_finalizada') {
            html += '<div style="background: #f0f8ff; border: 1px solid #2196f3; padding: 15px; border-radius: 4px;">';
            html += '<h4 style="margin-top: 0;">🎯 Processo Concluído</h4>';
            html += '<p>Esta contestação foi completamente resolvida através de votação comunitária. ';
            html += 'O resultado final foi aplicado ao lançamento e não são necessárias mais ações.</p>';
            if (contestacao.link_postagem_blog) {
                html += '<p><a href="' + contestacao.link_postagem_blog + '" target="_blank" class="button button-small">📝 Ver Post no Blog</a> ';
            }
            if (contestacao.link_formulario_votacao) {
                html += '<a href="' + contestacao.link_formulario_votacao + '" target="_blank" class="button button-small">🗳️ Ver Formulário de Votação</a></p>';
            }
            html += '</div>';
        } else if (contestacao.estado === 'expirada') {
            html += '<div style="background: #ffeaa7; border: 1px solid #fdcb6e; padding: 15px; border-radius: 4px;">';
            html += '<h4 style="margin-top: 0;">⏰ Disputa Expirada</h4>';
            html += '<p>Esta disputa não foi resolvida dentro do prazo estabelecido e foi automaticamente encerrada. ';
            html += 'O lançamento foi marcado como "RETIFICADO PELA COMUNIDADE" conforme as regras do sistema.</p>';
            if (contestacao.observacoes_finais) {
                html += '<p><strong>Observações:</strong> ' + contestacao.observacoes_finais + '</p>';
            }
            if (contestacao.link_postagem_blog) {
                html += '<p><a href="' + contestacao.link_postagem_blog + '" target="_blank" class="button button-small">📝 Ver Post no Blog</a> ';
            }
            if (contestacao.link_formulario_votacao) {
                html += '<a href="' + contestacao.link_formulario_votacao + '" target="_blank" class="button button-small">🗳️ Ver Formulário de Votação</a></p>';
            }
            html += '</div>';
        } else if (!temAcoes) {
            html += '<p><em>Nenhuma ação disponível para o estado atual desta contestação.</em></p>';
        }
        
        html += '<p><button type="button" class="button gc-fechar-modal">Fechar</button></p>';
        html += '</div>';
        
        html += '</div>';
        
        gc_abrir_modal(html);
        
        // Conectar botões de ação dentro do modal
        $('.gc-responder-contestacao-modal').on('click', function() {
            console.log('Clicou em Responder Contestação');
            gc_fechar_modal();
            gc_abrir_modal_resposta_contestacao($(this).data('id'));
        });
        
        $('.gc-analisar-contestacao-modal').on('click', function() {
            console.log('Clicou em Analisar Contestação');
            gc_fechar_modal();
            gc_abrir_modal_analise_contestacao($(this).data('id'));
        });
        
        $('.gc-finalizar-disputa-modal').on('click', function() {
            console.log('Clicou em Finalizar Disputa no modal');
            gc_fechar_modal();
            gc_abrir_modal_finalizar_disputa($(this).data('id'));
        });
        
        $('.gc-registrar-resultado-modal').on('click', function() {
            console.log('Clicou em Registrar Resultado no modal');
            gc_fechar_modal();
            gc_abrir_modal_registrar_resultado($(this).data('id'));
        });
    }
    
    function gc_abrir_modal_lista_contestacoes(lancamentoId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_listar_contestacoes_lancamento',
                lancamento_id: lancamentoId,
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var dados = response.data;
                    var html = '<div class="gc-modal-lista-contestacoes">';
                    html += '<h3>Contestações do Lançamento #' + dados.lancamento.numero_unico + '</h3>';
                    
                    if (dados.contestacoes.length > 0) {
                        html += '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr>';
                        html += '<th>Tipo</th>';
                        html += '<th>Estado</th>';
                        html += '<th>Autor</th>';
                        html += '<th>Data</th>';
                        html += '<th>Ações</th>';
                        html += '</tr></thead>';
                        html += '<tbody>';
                        
                        dados.contestacoes.forEach(function(contestacao) {
                            html += '<tr>';
                            html += '<td>' + contestacao.tipo + '</td>';
                            html += '<td><span class="gc-badge gc-contestacao-' + contestacao.estado + '">' + contestacao.estado.replace('_', ' ') + '</span></td>';
                            html += '<td>' + contestacao.autor_nome + '</td>';
                            html += '<td>' + new Date(contestacao.data_criacao).toLocaleDateString('pt-BR') + '</td>';
                            html += '<td><button type="button" class="button button-small gc-ver-contestacao" data-id="' + contestacao.id + '">Ver</button></td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    } else {
                        html += '<p>Nenhuma contestação encontrada para este lançamento.</p>';
                    }
                    
                    html += '<p><button type="button" class="button gc-fechar-modal">Fechar</button></p>';
                    html += '</div>';
                    
                    gc_abrir_modal(html);
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Erro ao carregar contestações');
            }
        });
    }
    
    function gc_abrir_modal_lancamento_simples(tipo) {
        var html = '<div class="gc-modal-lancamento-simples">';
        html += '<h3>' + (tipo === 'receita' ? 'Nova Doação' : 'Nova Despesa') + '</h3>';
        html += '<form id="form-lancamento-simples">';
        
        html += '<table class="form-table">';
        html += '<tr>';
        html += '<th><label>Descrição *</label></th>';
        html += '<td><input type="text" name="descricao_curta" required class="regular-text"></td>';
        html += '</tr>';
        
        html += '<tr>';
        html += '<th><label>Valor (R$) *</label></th>';
        html += '<td><input type="number" name="valor" required step="0.01" min="0.01" class="regular-text" id="valor-modal"></td>';
        html += '</tr>';
        
        if (tipo === 'receita') {
            html += '<tr id="tr-doacao-anonima-modal">';
            html += '<th><label>Doação Anônima</label></th>';
            html += '<td>';
            html += '<label><input type="checkbox" name="doacao_anonima" id="doacao_anonima_modal"> Não mostrar meu nome publicamente</label>';
            html += '<div id="aviso-limite-modal" class="notice" style="display: none; margin-top: 10px; padding: 10px;">';
            html += '<p id="texto-aviso-modal"></p>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        }
        
        html += '<tr>';
        html += '<th><label>Detalhes</label></th>';
        html += '<td><textarea name="descricao_detalhada" rows="3" class="large-text"></textarea></td>';
        html += '</tr>';
        html += '</table>';
        
        html += '<input type="hidden" name="tipo" value="' + tipo + '">';
        html += '<p>';
        html += '<button type="submit" class="button button-primary">Criar ' + (tipo === 'receita' ? 'Doação' : 'Despesa') + '</button> ';
        html += '<button type="button" class="button gc-fechar-modal">Cancelar</button>';
        html += '</p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
        // Handler para verificar limite anônimo no modal
        if (tipo === 'receita') {
            $('#valor-modal').on('input change', function() {
                verificarLimiteAnonimoModal();
            });
            
            function verificarLimiteAnonimoModal() {
                var valor = parseFloat($('#valor-modal').val()) || 0;
                
                if (valor <= 0) {
                    $('#aviso-limite-modal').hide();
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gc_verificar_limite_anonimo',
                        valor: valor,
                        nonce: gc_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var dados = response.data;
                            var $checkbox = $('#doacao_anonima_modal');
                            var $aviso = $('#aviso-limite-modal');
                            var $textoAviso = $('#texto-aviso-modal');
                            
                            if (!dados.pode_anonimo) {
                                $checkbox.prop('disabled', true).prop('checked', false);
                                $aviso.show().removeClass('notice-info').addClass('notice-warning');
                                
                                if (dados.excede_limite_valor) {
                                    $textoAviso.html('<strong>⚠️ Doação será identificada publicamente</strong><br>' +
                                        'Doações acima de R$ ' + parseFloat(dados.valor_maximo).toFixed(2).replace('.', ',') + ' têm identificação obrigatória.');
                                } else if (dados.excede_limite_mensal) {
                                    $textoAviso.html('<strong>⚠️ Limite mensal excedido</strong><br>' +
                                        'Você já doou R$ ' + parseFloat(dados.total_mes).toFixed(2).replace('.', ',') + ' anonimamente este mês.');
                                }
                            } else {
                                $checkbox.prop('disabled', false);
                                $aviso.show().removeClass('notice-warning').addClass('notice-info');
                                $textoAviso.html('<strong>ℹ️ Doação pode ser anônima</strong><br>' +
                                    'Limite restante: R$ ' + parseFloat(dados.limite_restante).toFixed(2).replace('.', ','));
                            }
                        }
                    }
                });
            }
        }
        
        // Handler para submissão do formulário
        $('#form-lancamento-simples').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'gc_criar_lancamento');
            formData.append('nonce', gc_ajax.nonce);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Lançamento criado com sucesso!');
                        gc_fechar_modal();
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar solicitação');
                }
            });
        });
    }
});