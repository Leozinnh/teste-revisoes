-- 12 - Próximas revisões estimadas
-- Regra: última revisão + média de dias entre as revisões do veículo.
-- Veículos com apenas uma revisão não têm média e ficam de fora;
-- no sistema eles usam o prazo padrão (180 dias), em RelatorioController.

SELECT v.marca || ' ' || v.modelo || ' (' || v.placa || ')' AS veiculo,
       p.nome AS proprietario,
       MAX(r.data_revisao) AS ultima_revisao,
       ROUND(AVG(sub.intervalo_dias)) AS intervalo_medio_dias,
       MAX(r.data_revisao) + ROUND(AVG(sub.intervalo_dias)) AS proxima_revisao
FROM (
    SELECT r.veiculo_id,
           r.data_revisao - lag(r.data_revisao)
               OVER (PARTITION BY r.veiculo_id ORDER BY r.data_revisao) AS intervalo_dias
    FROM leonardo.revisoes r
) sub
JOIN leonardo.revisoes r ON r.veiculo_id = sub.veiculo_id
JOIN leonardo.veiculos v ON v.id = sub.veiculo_id
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
WHERE sub.intervalo_dias IS NOT NULL
GROUP BY v.id, v.marca, v.modelo, v.placa, p.nome
ORDER BY proxima_revisao;
