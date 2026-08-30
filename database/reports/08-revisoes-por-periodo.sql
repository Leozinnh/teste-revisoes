-- 08 - Revisões dentro de um período
-- Para testar, descomente o WHERE e ajuste as datas:
--   AND r.data_revisao >= '2026-01-01'
--   AND r.data_revisao <= '2026-12-31'
-- No sistema os filtros chegam pela API (?data_inicio=...&data_fim=...)

SELECT r.data_revisao,
       v.marca || ' ' || v.modelo || ' (' || v.placa || ')' AS veiculo,
       p.nome AS proprietario,
       r.quilometragem,
       r.descricao,
       r.valor
FROM leonardo.revisoes r
JOIN leonardo.veiculos v ON v.id = r.veiculo_id
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
ORDER BY r.data_revisao;
