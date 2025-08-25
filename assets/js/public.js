/* Gestão Coletiva - Public JavaScript */

jQuery(document).ready(function($) {
    
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
                    
                    // Redirecionar para visualização do lançamento
                    if (lancamento.numero_unico) {
                        window.location.href = '?gc_action=ver&numero=' + lancamento.numero_unico;
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
        
        // Se estamos em uma página com parâmetros, navegar
        if (window.location.search || window.location.pathname.indexOf('gc_action') === -1) {
            window.location.href = '?gc_action=ver&numero=' + numero;
        } else {
            // Se estamos no contexto do shortcode, fazer busca AJAX
            gc_buscar_lancamento_ajax(numero);
        }
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
    
    // Abrir contestação
    $(document).on('click', '.gc-abrir-contestacao', function() {
        var lancamentoId = $(this).data('id');
        
        // Verificar se o usuário está logado
        if (typeof gc_ajax === 'undefined' || !gc_ajax.is_logged_in) {
            alert('Você precisa estar logado para criar uma contestação. Faça login e tente novamente.');
            return;
        }
        
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
            window.location.href = '?gc_action=ver&numero=' + numero;
        }
    });
    
    // Funções auxiliares
    function gc_buscar_lancamento_ajax(numero) {
        var container = $('#gc-resultado-busca');
        if (container.length === 0) {
            // Se não temos container de resultado, navegar normalmente
            window.location.href = '?gc_action=ver&numero=' + numero;
            return;
        }
        
        container.html('<p>Buscando...</p>');
        
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
                if (response.success) {
                    container.html(response.data);
                } else {
                    container.html('<p>Lançamento não encontrado. Verifique o número e tente novamente.</p>');
                }
            },
            error: function() {
                container.html('<p>Erro ao buscar lançamento. Tente novamente.</p>');
            }
        });
    }
    
    function gc_abrir_modal_contestacao(lancamentoId) {
        var html = '<div class="gc-modal-contestacao">';
        html += '<h3>Abrir Contestação</h3>';
        html += '<form id="form-contestacao">';
        html += '<p><label>Tipo de Contestação:</label>';
        html += '<select name="tipo" required>';
        html += '<option value="">Selecione...</option>';
        html += '<option value="doacao_nao_contabilizada">Uma doação não foi contabilizada</option>';
        html += '<option value="despesa_nao_verificada">Uma despesa não pôde ser verificada</option>';
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
        var html = '<div class="gc-certificado">';
        html += '<div class="gc-certificado-header">';
        html += '<h2>🏆 Certificado de Doação</h2>';
        html += '</div>';
        html += '<div class="gc-certificado-content">';
        html += '<div class="gc-cert-info">';
        html += '<p><strong>Número:</strong> #' + certificado.numero_unico + '</p>';
        html += '<p><strong>Doador:</strong> ' + certificado.autor + '</p>';
        html += '<p><strong>Descrição:</strong> ' + certificado.descricao_curta + '</p>';
        if (certificado.descricao_detalhada) {
            html += '<p><strong>Detalhes:</strong> ' + certificado.descricao_detalhada + '</p>';
        }
        html += '<p><strong>Valor:</strong> R$ ' + parseFloat(certificado.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '</p>';
        html += '<p><strong>Data:</strong> ' + gc_formatarData(certificado.data_efetivacao) + '</p>';
        html += '</div>';
        html += '<div class="gc-agradecimento">';
        html += '<p>' + certificado.texto_agradecimento + '</p>';
        html += '</div>';
        html += '</div>';
        html += '<div class="gc-certificado-actions">';
        html += '<button type="button" class="gc-btn gc-btn-primary" onclick="gc_imprimirCertificado()">Imprimir</button>';
        html += ' <button type="button" class="gc-btn gc-btn-outline gc-fechar-modal">Fechar</button>';
        html += '</div>';
        html += '</div>';
        
        gc_abrir_modal(html);
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
        
        // Botão fechar
        $('.gc-fechar-modal').on('click', function() {
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
});