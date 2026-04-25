# Controle de Inventario de Equipamentos

MVP funcional para controle fisico de ativos da empresa, com foco em:

- cadastro de computadores, celulares e tablets
- controle de responsavel atual e localizacao
- historico de movimentacoes
- QR Code para consulta e atualizacao rapida via celular
- ponto inicial de integracao com OCS Inventory via mock/export

## Stack escolhida

- PHP 8.2
- MySQL
- HTML/CSS/JS sem framework

Essa escolha prioriza rapidez de entrega, simplicidade operacional e facilidade de evolucao.

## Como rodar

1. Coloque a pasta dentro do Laragon ou execute com PHP embutido.
2. Garanta que o MySQL do Laragon esteja iniciado.
3. Acesse `http://localhost/Controle%20de%20equipamentos/index.php`
4. O banco `controle_equipamentos` e os dados iniciais sao criados automaticamente no primeiro acesso.
<<<<<<< HEAD
5. A pasta `txt/` contem a estrutura MySQL do projeto em arquivos texto separados, com as tabelas em portugues.
=======
>>>>>>> 64008ce534cb8635856c9f616a1c1fa1d0899102

Se quiser subir sem Laragon:

```bash
php -S localhost:8000
```

Depois acesse `http://localhost:8000/index.php`.

## O que o MVP entrega

### Cadastro de equipamentos

- tipo do equipamento
- codigo/identificacao
- nome do ativo
- numero de serie
- status
- responsavel atual
- localizacao atual
- referencia OCS

### Controle de posse e localizacao

- visualizacao centralizada do inventario
- tela de detalhe com atualizacao rapida
- historico basico de movimentacoes

### QR Code

- cada equipamento possui um QR Code proprio
- ao ler, a pessoa abre a tela mobile do ativo
- nessa tela e possivel atualizar responsavel, local e status rapidamente
- tambem existe uma pagina com leitura via camera do navegador: `scanner.php`

Observacao: a imagem do QR e gerada via [QuickChart](https://quickchart.io/) neste MVP. Em uma proxima fase, podemos internalizar a geracao para funcionar 100% offline.

### Integracao com OCS

- pagina `ocs.php` cruza o inventario local com `storage/data/ocs_mock.json`
- hoje o fluxo funciona como mock/export inicial
- proxima evolucao natural: importacao automatica por API ou arquivo exportado do OCS

## Estrutura

- `index.php`: dashboard e listagem principal
- `equipamento.php`: cadastro e edicao
- `ativo.php`: detalhe do equipamento, QR e historico
- `qr.php`: tela mobile para atualizacao rapida
- `scanner.php`: leitura por camera no navegador
- `ocs.php`: comparativo com OCS
- `src/bootstrap.php`: conexao MySQL, criacao do banco, seeds e funcoes compartilhadas
<<<<<<< HEAD
- `txt/`: scripts texto com banco, tabelas `equipamentos` e `movimentacoes`, e seed inicial em MySQL
=======
>>>>>>> 64008ce534cb8635856c9f616a1c1fa1d0899102

## Diferencial de IA

O projeto foi pensado para crescimento incremental e ja inclui uma area de "sugestoes operacionais" que destaca itens sem responsavel ou em manutencao. Isso abre caminho para evoluir para regras mais inteligentes ou integracao com IA real depois.

## Proximos passos sugeridos

- autenticar usuarios e registrar quem fez cada mudanca
- exportar relatorios em PDF/Excel
- sincronizar com OCS por API
- internalizar a geracao do QR Code
- adicionar permissao por perfil
