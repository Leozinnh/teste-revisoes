-- 09 - Marcas com maior número de revisões

SELECT v.marca,
       COUNT(*) AS quantidade
FROM leonardo.revisoes r
JOIN leonardo.veiculos v ON v.id = r.veiculo_id
GROUP BY v.marca
ORDER BY quantidade DESC;
