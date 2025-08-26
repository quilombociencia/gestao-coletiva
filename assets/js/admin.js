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
    
    // Corrigir estados de contestação
    $('#btn-corrigir-estados').on('click', function() {
        var button = $(this);
        
        if (!confirm('Deseja corrigir as contestações com estado incorreto? Esta ação corrigirá contestações "rejeitadas" para "em_disputa".')) {
            return;
        }
        
        button.prop('disabled', true).text('Corrigindo...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_corrigir_estados',
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Erro: ' + response.data);
                }
                button.prop('disabled', false).text('Corrigir Estados de Contestação');
            },
            error: function() {
                alert('Erro ao processar solicitação');
                button.prop('disabled', false).text('Corrigir Estados de Contestação');
            }
        });
    });
    
    // Atualizar estrutura do banco
    $('#btn-atualizar-estrutura').on('click', function() {
        var button = $(this);
        
        if (!confirm('Deseja atualizar a estrutura da tabela de contestações? Esta ação adicionará novos campos necessários para a funcionalidade de votação.')) {
            return;
        }
        
        button.prop('disabled', true).text('Atualizando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_atualizar_estrutura',
                nonce: gc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Erro: ' + response.data);
                }
                button.prop('disabled', false).text('Atualizar Estrutura do Banco');
            },
            error: function() {
                alert('Erro ao processar solicitação');
                button.prop('disabled', false).text('Atualizar Estrutura do Banco');
            }
        });
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
        console.log('Dados da contestação:', dados);
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
        html += '<p><strong>Estado Atual:</strong> <span class="gc-badge">' + lancamento.estado.replace('_', ' ').toUpperCase() + '</span></p>';
        html += '<p><strong>Autor do Lançamento:</strong> ' + lancamento.autor_nome + '</p>';
        html += '</div>';
        
        // Informações da contestação
        html += '<div class="gc-section">';
        html += '<h4>⚖️ Detalhes da Contestação</h4>';
        html += '<p><strong>Tipo:</strong> ' + (contestacao.tipo === 'doacao_nao_contabilizada' ? 'Doação não contabilizada' : 'Despesa não verificada') + '</p>';
        html += '<p><strong>Estado:</strong> <span class="gc-badge gc-contestacao-' + contestacao.estado + '">' + contestacao.estado.replace('_', ' ').toUpperCase() + '</span></p>';
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
        
        if (contestacao.data_resolucao_final) {
            html += '<li>🏁 <strong>Disputa resolvida</strong> - ' + new Date(contestacao.data_resolucao_final).toLocaleDateString('pt-BR');
            if (contestacao.resultado_votacao) {
                html += '<br><strong>Resultado:</strong> ' + (contestacao.resultado_votacao === 'contestacao_procedente' ? 'Contestação Procedente' : 'Contestação Improcedente');
            }
            if (contestacao.observacoes_finais) {
                html += '<br><em>' + contestacao.observacoes_finais + '</em>';
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
        
        if (dados.pode_registrar_resultado && contestacao.estado === 'disputa_finalizada') {
            html += '<p><button type="button" class="button button-primary gc-registrar-resultado-modal" data-id="' + contestacao.id + '">🗳️ Registrar Resultado da Votação</button></p>';
            html += '<p class="description">Informe o resultado da votação comunitária para encerrar definitivamente a disputa.</p>';
            temAcoes = true;
        }
        
        if (!temAcoes) {
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
});