/* Gestão Coletiva - Admin JavaScript */

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
        html += '<select name="novo_estado" required>';
        html += '<option value="">Selecione...</option>';
        html += '<option value="procedente">Contestação Procedente (lançamento será contestado)</option>';
        html += '<option value="improcedente">Contestação Improcedente (lançamento será confirmado)</option>';
        html += '</select></p>';
        html += '<p><button type="submit" class="button button-primary">Enviar Resposta</button>';
        html += ' <button type="button" class="button gc-fechar-modal">Cancelar</button></p>';
        html += '</form>';
        html += '</div>';
        
        gc_abrir_modal(html);
        
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
        var html = '<div class="gc-modal-contestacao">';
        html += '<h3>Abrir Contestação</h3>';
        html += '<form id="form-contestacao-admin">';
        html += '<p><label>Tipo de Contestação:</label>';
        html += '<select name="tipo" required>';
        html += '<option value="">Selecione...</option>';
        html += '<option value="doacao_nao_contabilizada">Doação não foi contabilizada</option>';
        html += '<option value="despesa_nao_verificada">Despesa não pôde ser verificada</option>';
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
        var html = '<div class="gc-certificado">';
        html += '<h2>Certificado de Doação</h2>';
        html += '<div class="gc-certificado-content">';
        html += '<p><strong>Número:</strong> #' + certificado.numero_unico + '</p>';
        html += '<p><strong>Tipo:</strong> ' + certificado.tipo + '</p>';
        html += '<p><strong>Doador:</strong> ' + certificado.autor + '</p>';
        html += '<p><strong>Descrição:</strong> ' + certificado.descricao_curta + '</p>';
        if (certificado.descricao_detalhada) {
            html += '<p><strong>Detalhes:</strong> ' + certificado.descricao_detalhada + '</p>';
        }
        html += '<p><strong>Valor:</strong> R$ ' + parseFloat(certificado.valor).toFixed(2).replace('.', ',') + '</p>';
        html += '<p><strong>Data:</strong> ' + certificado.data_efetivacao + '</p>';
        html += '<div class="gc-agradecimento">' + certificado.texto_agradecimento + '</div>';
        html += '</div>';
        html += '<p><button type="button" class="button gc-fechar-modal">Fechar</button></p>';
        html += '</div>';
        
        gc_abrir_modal(html);
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
            maxWidth: '600px',
            maxHeight: '80vh',
            overflow: 'auto',
            position: 'relative'
        }).html(content);
        
        $modal.append($content);
        $('body').append($modal);
        
        // Fechar ao clicar fora
        $modal.on('click', function(e) {
            if (e.target === this) {
                gc_fechar_modal();
            }
        });
        
        // Botão fechar
        $('.gc-fechar-modal').on('click', function() {
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
});