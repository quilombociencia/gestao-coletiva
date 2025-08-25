# Gestão Coletiva v1.0.1 - Registro de Mudanças

## Resumo da Versão
Esta atualização implementa uma importante melhoria de segurança, restringindo o registro de despesas apenas a administradores, mantendo a transparência do sistema enquanto aumenta o controle sobre os gastos.

## 🔒 Alterações de Segurança

### Restrição de Permissões para Despesas
- **Mudança Principal**: Apenas usuários com permissão `manage_options` (administradores) podem registrar despesas
- **Impacto**: Authors e outros usuários não podem mais registrar gastos do projeto
- **Benefício**: Maior controle e segurança na gestão de despesas

### Permissões Mantidas
- **Receitas/Doações**: Continuam permitidas para usuários `authors+` ou através do filtro personalizado `gc_pode_criar_lancamento`
- **Contestações**: Permanecem abertas a todos usuários logados
- **Visualizações**: Livro-caixa e lançamentos continuam públicos

## 📝 Atualizações de Informações

### Correções de Metadados
- **Licença**: Corrigida de "GPL/PNU 3.0" para "GPL/GNU 3.0"
- **Autor**: Atualizado para "Quilombo Ciência"
- **Repositório**: Definido como "https://github.com/quilombociencia/gestao-coletiva"

### Versão
- **De**: 1.0.0
- **Para**: 1.0.1

## 🎨 Melhorias na Interface

### Interface Pública
- Botão "Incluir Despesa" no painel principal (`public/views/painel.php`) agora só aparece para administradores
- Seção de despesas em lançamentos (`public/views/lancamentos.php`) restrita a administradores

### Feedback ao Usuário
- Mensagem de erro clara quando usuários não-administradores tentam registrar despesas
- Mantida a experiência para doações/receitas

## 📚 Documentação Atualizada

### README.md
- **Estrutura de arquivos**: Expandida com mais detalhes sobre cada componente
- **Seção de permissões**: Clarificada com as novas restrições
- **Exemplo de filtro**: Atualizado para mostrar que despesas são sempre controladas internamente
- **Changelog**: Adicionado registro completo das mudanças

## 🔧 Arquivos Modificados

1. **gestao-coletiva.php**
   - Header: Version 1.0.1, License GPL/GNU 3.0
   - Constante: GC_VERSION = '1.0.1'

2. **includes/class-gc-admin.php**
   - Método `ajax_criar_lancamento()`: Verificação de permissão para despesas

3. **public/views/painel.php**
   - Condição de visibilidade do botão "Incluir Despesa"

4. **public/views/lancamentos.php**
   - Condição de visibilidade da seção de despesas

5. **README.md**
   - Informações do projeto, permissões, changelog, exemplos

## 🚀 Implementação

### Para Desenvolvedores
Esta atualização mantém total compatibilidade com a versão anterior. Não há mudanças no banco de dados ou estruturas existentes.

### Para Usuários Finais
- **Administradores**: Nenhuma mudança no fluxo de trabalho
- **Authors/Editores**: Não podem mais registrar despesas, mas mantêm acesso a doações
- **Visitantes**: Nenhuma mudança na experiência de visualização

## 🔍 Código de Exemplo

### Nova Verificação de Permissão
```php
// Verificar permissões: apenas administradores podem registrar despesas
if ($tipo === 'despesa' && !current_user_can('manage_options')) {
    wp_send_json_error(__('Apenas administradores podem registrar despesas.', 'gestao-coletiva'));
    return;
}
```

### Interface Atualizada
```php
// Botão de despesa só para administradores
<?php if (is_user_logged_in() && current_user_can('manage_options')): ?>
    <div class="gc-acao-card" data-acao="incluir-despesa">
        <!-- Conteúdo do botão -->
    </div>
<?php endif; ?>
```

## 📋 Checklist de Atualização

- [x] Versão atualizada no arquivo principal
- [x] Constante GC_VERSION atualizada
- [x] Verificação de permissões implementada
- [x] Interface atualizada para ocultar controles de despesas
- [x] README atualizado com novas informações
- [x] Changelog documentado
- [x] Compatibilidade verificada

---

**Data**: 25 de agosto de 2025  
**Tipo de Atualização**: Patch de Segurança  
**Compatibilidade**: WordPress 5.0+, PHP 7.4+