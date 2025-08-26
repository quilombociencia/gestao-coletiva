# Changelog - Versão 1.1.0

## 🎉 Novidades

### ✨ Configuração PIX Integrada
- **Campos PIX nas configurações**: Chave PIX e nome do beneficiário podem ser configurados no painel admin
- **Exibição dinâmica**: Informações PIX aparecem automaticamente nos formulários de doação
- **Cópia automática**: Um clique na chave PIX copia automaticamente para facilitar as doações
- **Visual destacado**: Seção PIX com design atrativo e intuitivo

### 🎨 Interface Melhorada
- **"Dashboard" → "Painel"**: Terminologia atualizada para português
- **Seção PIX em lançamentos**: Doações exibem automaticamente as informações PIX configuradas
- **Feedback visual**: Avisos quando PIX não está configurado
- **Estilos aprimorados**: CSS específico para melhor apresentação das informações PIX

### 🏗️ Melhorias Técnicas
- **Limpeza de código**: Removidos botões de correção manual do banco de dados
- **Instalação automática**: Estrutura do banco atualizada automaticamente em instalações novas
- **Segurança**: Escape adequado de dados PIX em JavaScript e HTML

### 🐛 Correções
- **Lógica de contabilização**: Estados 'confirmado', 'aceito' e 'retificado_comunidade' agora são corretamente contabilizados
- **Certificados**: Disponíveis para todos os estados de valores confirmados
- **Contestações**: Possíveis contra qualquer valor já efetivamente confirmado

## 📋 Detalhes Técnicos

### Arquivos Modificados
- `gestao-coletiva.php` - Versão atualizada para 1.1.0
- `includes/class-gc-database.php` - Novas configurações PIX, criação automática de tabelas
- `admin/views/configuracoes.php` - Seção PIX adicionada
- `includes/class-gc-admin.php` - Handler AJAX para campos PIX, "Dashboard" → "Painel"
- `public/views/painel.php` - Informações PIX dinâmicas no modal de doação
- `admin/views/lancamentos/criar.php` - Informações PIX dinâmicas no admin
- `public/views/lancamentos.php` - Seção PIX em lançamentos, lógica de contestação atualizada
- `assets/css/public.css` - Estilos para seção PIX
- `assets/css/admin.css` - Estilos para novos estados de contestação
- `includes/class-gc-lancamento.php` - Lógica de contabilização corrigida
- `includes/class-gc-relatorio.php` - Cálculos incluindo todos os estados confirmados

### Banco de Dados
Novas configurações adicionadas automaticamente:
- `chave_pix` - Chave PIX para doações
- `nome_beneficiario_pix` - Nome do beneficiário

### Compatibilidade
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

---

**Data de Lançamento**: $(date '+%d/%m/%Y')  
**Versão Anterior**: 1.0.2  
**Desenvolvedor**: Quilombo Ciência