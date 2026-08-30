-- 06 - Todas as pessoas, com a quantidade de veículos

SELECT p.nome,
       p.cpf,
       p.sexo,
       p.data_nascimento,
       p.email,
       p.telefone,
       COUNT(v.id) AS veiculos
FROM leonardo.pessoas p
LEFT JOIN leonardo.veiculos v ON v.pessoa_id = p.id
GROUP BY p.id
ORDER BY p.nome;
