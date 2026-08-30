-- 11 - Média de dias entre uma revisão e outra (por pessoa)
-- lag() é função de janela: devolve o valor da linha anterior na partição.
-- Aqui ela traz a data da revisão anterior de cada pessoa:
--   lag(r.data_revisao) OVER (PARTITION BY p.id ORDER BY r.data_revisao)
-- A subtração das datas dá o intervalo em dias, e o AVG tira a média.

SELECT p.nome,
       ROUND(AVG(sub.intervalo_dias)) AS media_dias
FROM (
    SELECT p.id AS pessoa_id,
           r.data_revisao - lag(r.data_revisao)
               OVER (PARTITION BY p.id ORDER BY r.data_revisao) AS intervalo_dias
    FROM leonardo.revisoes r
    JOIN leonardo.veiculos v ON v.id = r.veiculo_id
    JOIN leonardo.pessoas p ON p.id = v.pessoa_id
) sub
JOIN leonardo.pessoas p ON p.id = sub.pessoa_id
WHERE sub.intervalo_dias IS NOT NULL
GROUP BY p.id, p.nome
ORDER BY media_dias DESC;
