<?php

namespace App\Support;

class PaeeSkills
{
    /**
     * Níveis do resumo por área.
     *
     * @return array<string, string>
     */
    public static function areaLevels(): array
    {
        return [
            'desenvolveu' => 'Desenvolveu',
            'desenvolveu_com_ajuda' => 'Desenvolveu com ajuda',
            'nao_desenvolveu' => 'Não desenvolveu',
            'as_vezes' => 'Às vezes',
        ];
    }

    /**
     * Níveis das habilidades detalhadas.
     *
     * @return array<string, string>
     */
    public static function skillLevels(): array
    {
        return [
            'realiza_sem_suporte' => 'Realiza sem necessidade de suporte',
            'realiza_com_ajuda' => 'Realiza com ajuda',
            'nao_realiza' => 'Não realiza',
            'nao_observado' => 'Não foi observado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function areas(): array
    {
        return [
            'linguagem_nao_verbal' => 'Linguagem não verbal',
            'linguagem_verbal' => 'Linguagem verbal',
            'conteudo_programatico' => 'Conteúdo programático',
            'aspecto_social' => 'Aspecto social',
            'aspecto_motor' => 'Aspecto motor',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function groups(): array
    {
        return [
            'COMUNICAÇÃO ORAL' => [
                1 => 'Relata acontecimentos simples de modo compreensível',
                2 => 'Se lembra de dar recados após, aproximadamente, 10 minutos',
                3 => 'Comunica-se com outras pessoas usando outro tipo de linguagem (gestos, comunicação alternativa) que não a oral',
                4 => 'Utiliza a linguagem oral para se comunicar',
            ],
            'LEITURA E ESCRITA' => [
                5 => 'Conhece as letras do alfabeto',
                6 => 'Reconhece a diferença entre letras e números',
                7 => 'Domina sílabas simples',
                8 => 'Ouve histórias com atenção',
                9 => 'Consegue compreender e reproduzir histórias',
                10 => 'Participa de jogos, atendendo às regras',
                11 => 'Utiliza vocabulário adequado para a faixa etária',
                12 => 'Sabe soletrar',
                13 => 'Consegue escrever palavras simples',
                14 => 'É capaz de assinar seu nome',
                15 => 'Escreve endereços (com o objetivo de saber aonde chegar)',
                16 => 'Escreve pequenos textos e/ou bilhetes',
                17 => 'Escreve sob ditado',
                18 => 'Lê com compreensão pequenos textos',
                19 => 'Lê e segue instruções impressas, por exemplo: em transportes públicos',
                20 => 'Utiliza habilidades de leitura para informações, por exemplo: em jornais e revistas',
            ],
            'RACIOCÍNIO LÓGICO-MATEMÁTICO' => [
                21 => 'Relaciona quantidade ao número',
                22 => 'Soluciona problemas simples',
                23 => 'Reconhece os valores dos preços dos produtos',
                24 => 'Identifica o valor do dinheiro',
                25 => 'Diferencia notas e moedas',
                26 => 'Sabe agrupar dinheiro para formar valores',
                27 => 'Dá troco, quando necessário, nas atividades realizadas em sala de aula',
                28 => 'Possui conceitos como: cor, tamanho, formas geométricas, posição direita e esquerda, antecessor e sucessor',
                29 => 'Reconhece a relação entre número e dias do mês (localização temporal)',
                30 => 'Identifica dias da semana',
                31 => 'Reconhece as horas',
                32 => 'Reconhece horas em relógio digital',
                33 => 'Reconhece horas exatas em relógio com ponteiro',
                34 => 'Reconhece horas não exatas (meia hora, 7 minutos)',
                35 => 'Associa horários aos acontecimentos',
                36 => 'Reconhece as medidas de tempo (ano, hora, minuto, dia...)',
                37 => 'Compreende conceitos matemáticos, como dobro e metade',
                38 => 'Resolve operações matemáticas (adição ou subtração)',
                39 => 'Demonstra curiosidade, gosta de perguntar sobre o funcionamento das coisas',
                40 => 'Gosta de jogos envolvendo lógica, como por exemplo: quebra-cabeça, charadas',
                41 => 'Organiza figuras em ordem lógica',
                42 => 'Sabe contar em sequência',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $all = [];
        foreach (self::groups() as $skills) {
            foreach ($skills as $number => $label) {
                $all[$number] = $label;
            }
        }

        return $all;
    }
}
