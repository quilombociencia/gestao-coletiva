# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.1.1] - 2025-01-09

### ✨ Adicionado
- Sistema completo de verificação de autenticidade de certificados
- Novo shortcode `[gc_verificar_certificado]` para páginas de verificação
- Informações de recorrência incluídas nos certificados de doação
- Interface de verificação automática via URL com parâmetros
- Estilos CSS aprimorados para impressão de certificados

### 🔧 Corrigido
- **CRÍTICO**: Resolvido erro 404 nas views públicas ao consultar lançamentos
- Substituídos redirecionamentos problemáticos por modais AJAX nas views:
  - Painel público (`gc_painel`)
  - Lançamentos (`gc_lancamentos`) 
  - Livro-caixa (`gc_livro_caixa`)
- Funcionalidade de impressão de certificados agora funciona corretamente
- Botões "Ver" nas tabelas de lançamentos usam modais em vez de redirecionamentos

### 🔒 Segurança
- Restrição de emissão de certificados apenas para:
  - Autor da doação original
  - Administradores do sistema
- Verificação de permissões no backend para geração de certificados

### 🎨 Melhorado
- Interface mais fluida com uso de modais AJAX
- Melhor experiência do usuário sem redirecionamentos desnecessários
- Certificados com informações mais completas sobre recorrência
- Funções JavaScript organizadas como globais para melhor compatibilidade

---

## [1.1.0] - 2025-01-08

### ✨ Adicionado
- **Integração PIX**: Sistema completo de pagamentos PIX
- Configuração de chave PIX e beneficiário nas configurações
- Interface aprimorada para doações com instruções PIX em tempo real
- Dashboard administrativo com logo personalizável
- Sistema de upload de logo da organização

### 🔧 Corrigido
- Interface administrativa reorganizada com melhor usabilidade
- Formulários de criação de lançamentos aprimorados
- Validações de campos melhoradas

### 🎨 Melhorado
- Design responsivo para dispositivos móveis
- Cores e estilos padronizados
- Experiência do usuário nas doações via PIX

---

## [1.0.2] - 2025-01-07

### ✨ Adicionado
- **Sistema de Contestações**: Funcionalidade completa para contestar lançamentos
- Interface para criação, análise e resolução de contestações
- Estados de lançamentos específicos para disputas
- Dashboard de contestações para administradores

### 🔧 Corrigido
- Lógica de estados nas contestações
- Botões funcionais no sistema de contestações
- Correções gerais no fluxo de contestações

---

## [1.0.1] - 2025-01-06

### 🔒 Segurança
- Restrição de permissões para criação de despesas
- Apenas administradores podem registrar despesas
- Validações de permissões aprimoradas

### 🔧 Corrigido
- Controle de acesso para diferentes tipos de usuários
- Validações de segurança nos formulários

---

## [1.0.0] - 2025-01-05

### ✨ Lançamento Inicial
- Sistema básico de gestão coletiva de recursos
- Criação e gestão de lançamentos (receitas e despesas)
- Sistema de aprovação e estados de lançamentos
- Relatórios financeiros básicos
- Painel público com transparência
- Livro-caixa público para prestação de contas
- Sistema de recorrência para doações regulares
- Interface administrativa completa

### 🎯 Recursos Principais
- **Gestão de Lançamentos**: Criação, edição e acompanhamento
- **Sistema de Estados**: Fluxo completo desde previsão até efetivação
- **Transparência**: Livro-caixa público e relatórios
- **Recorrência**: Doações automáticas mensais/anuais
- **Relatórios**: Geração de relatórios por período
- **Multi-usuário**: Sistema de permissões e roles

---

## Tipos de Mudanças

- `✨ Adicionado` para novas funcionalidades
- `🔧 Corrigido` para correção de bugs
- `🎨 Melhorado` para mudanças em funcionalidades existentes
- `🔒 Segurança` para correções relacionadas à segurança
- `📦 Dependências` para atualizações de dependências
- `🗑️ Removido` para funcionalidades removidas
- `⚠️ Depreciado` para funcionalidades que serão removidas em versões futuras

## Links

- [Repositório do Projeto](https://github.com/quilombociencia/gestao-coletiva)
- [Issues e Bugs](https://github.com/quilombociencia/gestao-coletiva/issues)
- [Documentação](https://github.com/quilombociencia/gestao-coletiva/blob/main/README.md)