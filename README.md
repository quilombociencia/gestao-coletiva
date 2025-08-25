# Gestão Coletiva - Plugin WordPress

## Sobre o Plugin

O **Gestão Coletiva** é um plugin WordPress para gestão transparente e coletiva de recursos de projetos. Permite arrecadação, gestão e prestação de contas em tempo real, com livro-caixa público e sistema completo de contestações. O plugin foi criado pelo Quilombo Ciência (quilombociencia.org) com o auxílio do modelo de linguagem Claude (claude.ai).

## Funcionalidades Principais

### 🏦 Gestão Financeira
- **Lançamentos**: Registro de receitas (doações) and despesas
- **Estados automáticos**: Previsto → Efetivado → Contestado/Aceito
- **Prazos automatizados**: Para efetivação, contestação e resolução
- **Recorrência**: Doações únicas, mensais, trimestrais ou anuais

### 🔍 Transparência Total
- **Livro-caixa público**: Visualização em tempo real de todas movimentações
- **Relatórios periódicos**: Mensais, trimestrais e anuais
- **Certificados de doação**: Comprovantes automáticos para doadores
- **Sistema de contestação**: Qualquer pessoa pode questionar lançamentos

### 🛡️ Sistema de Contestações
- **Tipos de contestação**:
  - Doação não contabilizada (enviar comprovante)
  - Despesa não verificada (solicitar comprovante)
- **Fluxo completo**: Contestação → Resposta → Análise → Resolução
- **Escalação comunitária**: Disputas não resolvidas vão para votação

### 💰 Doações via PIX
- **Interface simplificada** para registro de doações
- **Instruções automáticas** de PIX após registro
- **Verificação manual** pela administração
- **Certificados digitais** para doações efetivadas

## Instalação

1. Faça upload dos arquivos para `/wp-content/plugins/gestao-coletiva/`
2. Ative o plugin no painel administrativo do WordPress
3. Configure os prazos em **Gestão Coletiva → Configurações**
4. Customize as informações de PIX no código ou através de hooks

## Estrutura do Projeto

```
gestao-coletiva/
├── gestao-coletiva.php          # Arquivo principal com classe GestaoColetiva
├── includes/                    # Classes principais
│   ├── class-gc-database.php    # Gerenciamento do banco e configurações
│   ├── class-gc-lancamento.php  # Lógica de lançamentos financeiros
│   ├── class-gc-contestacao.php # Sistema completo de contestações
│   ├── class-gc-relatorio.php   # Relatórios e livro-caixa público
│   └── class-gc-admin.php       # Interface administrativa e AJAX
├── admin/views/                 # Telas administrativas
│   ├── dashboard.php            # Dashboard principal com resumos
│   ├── lancamentos.php          # Gerenciamento de lançamentos
│   │   ├── buscar.php          # Busca de lançamentos
│   │   ├── criar.php           # Criação de lançamentos
│   │   ├── listar.php          # Listagem completa
│   │   └── ver.php             # Visualização detalhada
│   ├── contestacoes.php         # Gerenciamento de contestações
│   ├── relatorios.php           # Relatórios e extratos
│   └── configuracoes.php        # Configurações do sistema
├── public/views/                # Interface pública (shortcodes)
│   ├── painel.php              # Painel principal público
│   ├── lancamentos.php         # Interface de lançamentos públicos
│   └── livro-caixa.php         # Livro-caixa público
├── assets/                      # CSS e JavaScript
│   ├── css/                    # Estilos para admin e público
│   │   ├── admin.css
│   │   └── public.css
│   └── js/                     # Scripts para admin e público
│       ├── admin.js
│       └── public.js
└── languages/                   # Traduções i18n
    └── gestao-coletiva.pot
```

## Shortcodes Disponíveis

### `[gc_painel]`
Exibe o painel principal com:
- Banner para contribuição
- Balanço financeiro atual
- Ações rápidas (doar, registrar despesa, consultar)
- Estatísticas de transparência

### `[gc_lancamentos]` 
Interface para:
- Criar novos lançamentos
- Consultar lançamentos por número
- Visualizar detalhes completos
- Gerar certificados

### `[gc_livro_caixa]`
Livro-caixa público com:
- Seletor de período
- Gráfico de evolução do saldo
- Lista detalhada de movimentações
- Resumo financeiro do período

## Estados dos Lançamentos

1. **Previsto**: Criado, aguardando verificação
2. **Efetivado**: Verificado e confirmado
3. **Cancelado**: Cancelado pelo autor
4. **Expirado**: Não verificado no prazo
5. **Em Contestação**: Questionado por usuário
6. **Contestado**: Contestação procedente
7. **Confirmado**: Contestação improcedente, aguardando análise
8. **Aceito**: Análise aceita pelo contestante
9. **Em Disputa**: Análise rejeitada, vai para comunidade
10. **Retificado/Contestado pela Comunidade**: Decisão final

## Fluxo de Contestações

1. **Usuário abre contestação** → Estado: "Em Contestação"
2. **Administrador/Autor responde** → Estado: "Confirmado" ou "Contestado"
3. **Contestante analisa resposta** → Estado: "Aceito" ou "Em Disputa"
4. **Se disputa**: Publicação no blog + Enquete da comunidade
5. **Decisão final**: "Retificado" ou "Contestado pela Comunidade"

## Configurações

### Prazos (em horas/dias)
- **Efetivação**: Tempo para verificar lançamentos (padrão: 72h)
- **Resposta a contestações**: Tempo para responder (padrão: 48h)
- **Análise de resposta**: Tempo para analisar resposta (padrão: 24h)
- **Publicação de disputa**: Tempo para publicar disputa (padrão: 24h)
- **Resolução**: Tempo para votação comunitária (padrão: 7 dias)

### Textos Customizáveis
- **Agradecimento nos certificados**
- **Informações de PIX** (através de hooks)

## Hooks e Filtros

### Actions
```php
// Após criação de lançamento
do_action('gc_lancamento_criado', $lancamento_id, $dados);

// Após mudança de estado
do_action('gc_lancamento_estado_alterado', $lancamento_id, $estado_anterior, $novo_estado);

// Processamento automático de vencimentos
do_action('gc_processar_vencimentos');
```

### Filters
```php
// Customizar informações de PIX
add_filter('gc_informacoes_pix', function($info) {
    $info['chave'] = 'sua-chave@pix.com';
    $info['beneficiario'] = 'Seu Projeto';
    return $info;
});

// Customizar permissões
add_filter('gc_pode_criar_lancamento', function($pode, $user_id, $tipo) {
    // Sua lógica personalizada
    return $pode;
}, 10, 3);
```

## Banco de Dados

### Tabelas Criadas
- `wp_gc_lancamentos`: Todos os lançamentos financeiros
- `wp_gc_contestacoes`: Sistema de contestações
- `wp_gc_relatorios`: Relatórios enviados manualmente
- `wp_gc_configuracoes`: Configurações do plugin

### Processamento Automático
- **Inicialização automática**: Plugin verifica e cria tabelas automaticamente
- **Sistema robusto**: Tratamento de erros e recuperação de falhas
- **Cron job**: Executa periodicamente para processar vencimentos
- **Estados automáticos**: Transições de estado baseadas em prazos
- **Log detalhado**: Registro de atividades no error_log do WordPress

## Segurança

### Permissões
- **Lançamentos**: Usuários logados (authors+)
- **Contestações**: Usuários logados
- **Administração**: Apenas administrators
- **Verificação**: Nonces em todas ações AJAX

### Validação
- **Sanitização** de todos inputs
- **Verificação de capacidades** do usuário
- **Escape de outputs** para prevenir XSS
- **Prepared statements** em todas consultas

## Desenvolvimento

### Requisitos
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

### Estrutura de Classes
- **GestaoColetiva**: Classe principal do plugin, gerencia inicialização e hooks
- **GC_Database**: Gerenciamento de tabelas, configurações e operações de banco
- **GC_Lancamento**: Lógica principal dos lançamentos financeiros
- **GC_Contestacao**: Sistema completo de contestações
- **GC_Relatorio**: Geração de relatórios e livro-caixa público
- **GC_Admin**: Interface administrativa e handlers AJAX

### Customização
O plugin foi desenvolvido para ser facilmente customizável:
- CSS classes bem definidas
- Hooks em pontos estratégicos
- Estrutura modular
- Código documentado

## Suporte

Para questões, bugs ou sugestões:
- Abra uma issue no repositório
- Contribuições via pull request são bem-vindas
- Documentação adicional no wiki

## Licença

GPL v2 ou posterior - mesma licença do WordPress.

## Changelog

### v1.0.0
- ✅ Lançamento inicial
- ✅ Sistema completo de lançamentos
- ✅ Sistema de contestações
- ✅ Livro-caixa público
- ✅ Certificados de doação
- ✅ Interface administrativa completa
- ✅ Processamento automático via cron
- ✅ Internacionalização (i18n)

---

**Desenvolvido para promover transparência e gestão coletiva responsável de recursos.**