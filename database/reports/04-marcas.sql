-- 04 - Marcas ordenadas pela quantidade de veículos

SELECT v.marca,
       COUNT(*) AS quantidade
FROM leonardo.veiculos v
GROUP BY v.marca
ORDER BY quantidade DESC;
