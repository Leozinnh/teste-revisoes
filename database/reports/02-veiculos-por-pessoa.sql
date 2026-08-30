-- 02 - Veículos por pessoa, ordenados pelo nome do proprietário

SELECT p.nome AS proprietario,
       v.placa,
       v.marca,
       v.modelo,
       v.ano
FROM leonardo.veiculos v
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
ORDER BY p.nome;
