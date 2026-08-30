-- 10 - Pessoas com maior número de revisões

SELECT p.nome,
       COUNT(*) AS quantidade
FROM leonardo.revisoes r
JOIN leonardo.veiculos v ON v.id = r.veiculo_id
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
GROUP BY p.id, p.nome
ORDER BY quantidade DESC;
