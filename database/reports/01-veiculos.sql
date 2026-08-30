-- 01 - Todos os veículos, com o proprietário

SELECT v.placa,
       v.marca,
       v.modelo,
       v.ano,
       p.nome AS proprietario
FROM leonardo.veiculos v
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
ORDER BY v.marca, v.modelo;
