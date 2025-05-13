<?php
/**
 * Calendário de Obrigações Fiscais e Contábeis (Versão Melhorada)
 * 
 * Este componente exibe um calendário com as obrigações fiscais e contábeis,
 * mostrando as obrigações diretamente nos dias do calendário.
 */

class FiscalCalendar {
    // Mês atual
    private $currentMonth;
    
    // Ano atual
    private $currentYear;
    
    // Dia atual
    private $currentDay;
    
    // Total de dias no mês
    private $daysInMonth;
    
    // Primeiro dia da semana (0 = Domingo, 1 = Segunda, etc.)
    private $firstDayOfWeek;
    
    // Obrigações fiscais e contábeis
    private $obligations = [];
    
    /**
     * Construtor
     */
    public function __construct($month = null, $year = null) {
        // Definir mês e ano atuais ou usar os fornecidos
        $this->currentMonth = $month ?: date('n');
        $this->currentYear = $year ?: date('Y');
        $this->currentDay = date('j');
        
        // Calcular o total de dias no mês
        $this->daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->currentMonth, $this->currentYear);
        
        // Calcular o primeiro dia da semana
        $timestamp = mktime(0, 0, 0, $this->currentMonth, 1, $this->currentYear);
        $this->firstDayOfWeek = date('w', $timestamp);
        
        // Carregar obrigações fiscais
        $this->loadObligations();
    }
    
    /**
     * Carregar obrigações fiscais e contábeis
     */
    private function loadObligations() {
        // Lista de obrigações para abril de 2025
        // Esta lista pode ser carregada de um banco de dados ou arquivo
        
        // Obrigações de abril de 2025
        if ($this->currentMonth == 4 && $this->currentYear == 2025) {
            $this->obligations = [
                5 => [
                    [
                        'title' => 'Comunicação de Operações Liquidadas em Dinheiro (COD)',
                        'description' => 'Comunicar operações liquidadas em dinheiro, decorrentes de alienação ou cessão onerosa ou gratuita de bens e direitos.',
                        'type' => 'federal',
                        'deadline' => 'Até o 5º dia útil'
                    ],
                    [
                        'title' => 'Salários',
                        'description' => 'Pagamento dos salários referentes ao mês anterior.',
                        'type' => 'trabalhista',
                        'deadline' => '5º dia útil'
                    ]
                ],
                7 => [
                    [
                        'title' => 'FGTS',
                        'description' => 'Recolhimento do FGTS referente ao mês anterior.',
                        'type' => 'trabalhista',
                        'deadline' => 'Dia 07'
                    ],
                    [
                        'title' => 'eSocial - Doméstico',
                        'description' => 'Recolhimento do DAE (Documento de Arrecadação do eSocial) para empregadores domésticos.',
                        'type' => 'trabalhista',
                        'deadline' => 'Dia 07'
                    ]
                ],
                10 => [
                    [
                        'title' => 'IPI',
                        'description' => 'Recolhimento do IPI para produtos classificados no código NCM 2402.20.00.',
                        'type' => 'federal',
                        'deadline' => 'Dia 10'
                    ]
                ],
                14 => [
                    [
                        'title' => 'EFD-Contribuições',
                        'description' => 'Entrega da EFD-Contribuições relativa ao mês de fevereiro de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 14'
                    ]
                ],
                15 => [
                    [
                        'title' => 'EFD-Reinf',
                        'description' => 'Entrega da EFD-Reinf relativa ao mês de março de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 15'
                    ],
                    [
                        'title' => 'DCTFWeb',
                        'description' => 'Entrega da DCTFWeb relativa ao mês de março de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 15'
                    ]
                ],
                20 => [
                    [
                        'title' => 'IRRF',
                        'description' => 'Recolhimento do IRRF sobre rendimentos do trabalho, juros de aplicações financeiras e outros.',
                        'type' => 'federal',
                        'deadline' => 'Dia 20'
                    ],
                    [
                        'title' => 'INSS',
                        'description' => 'Recolhimento da contribuição previdenciária (INSS) das empresas e equiparadas.',
                        'type' => 'previdenciario',
                        'deadline' => 'Dia 20'
                    ],
                    [
                        'title' => 'Simples Nacional',
                        'description' => 'Pagamento do DAS relativo ao mês de março de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 20'
                    ],
                    [
                        'title' => 'PGDAS-D',
                        'description' => 'Entrega da PGDAS-D referente ao período de apuração de março de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 20'
                    ]
                ],
                24 => [
                    [
                        'title' => 'IOF',
                        'description' => 'Recolhimento do IOF do 2º decêndio de abril de 2025.',
                        'type' => 'federal',
                        'deadline' => 'Dia 24'
                    ]
                ],
                25 => [
                    [
                        'title' => 'PIS/COFINS',
                        'description' => 'Recolhimento do PIS e da COFINS sobre faturamento e importação.',
                        'type' => 'federal',
                        'deadline' => 'Dia 25'
                    ]
                ],
                30 => [
                    [
                        'title' => 'IRPF',
                        'description' => 'Prazo final para entrega da Declaração de Imposto de Renda Pessoa Física 2025, ano-base 2024.',
                        'type' => 'federal',
                        'deadline' => 'Dia 30'
                    ],
                    [
                        'title' => 'DME',
                        'description' => 'Entrega da Declaração de Operações Liquidadas com Moeda em Espécie (DME).',
                        'type' => 'federal',
                        'deadline' => 'Dia 30'
                    ],
                    [
                        'title' => 'CSLL/IRPJ',
                        'description' => 'Recolhimento da CSLL e IRPJ para empresas do lucro real estimativa mensal.',
                        'type' => 'federal',
                        'deadline' => 'Dia 30'
                    ]
                ]
            ];
        } else {
            // Para outros meses, adicionar obrigações aqui
            // Exemplo de obrigações genéricas para qualquer mês
            $this->obligations = [
                5 => [
                    [
                        'title' => 'Salários',
                        'description' => 'Pagamento dos salários referentes ao mês anterior.',
                        'type' => 'trabalhista',
                        'deadline' => '5º dia útil'
                    ]
                ],
                7 => [
                    [
                        'title' => 'FGTS',
                        'description' => 'Recolhimento do FGTS referente ao mês anterior.',
                        'type' => 'trabalhista',
                        'deadline' => 'Dia 07'
                    ]
                ],
                15 => [
                    [
                        'title' => 'EFD-Reinf',
                        'description' => 'Entrega da EFD-Reinf relativa ao mês anterior.',
                        'type' => 'federal',
                        'deadline' => 'Dia 15'
                    ]
                ],
                20 => [
                    [
                        'title' => 'Simples Nacional',
                        'description' => 'Pagamento do DAS relativo ao mês anterior.',
                        'type' => 'federal',
                        'deadline' => 'Dia 20'
                    ]
                ],
                25 => [
                    [
                        'title' => 'PIS/COFINS',
                        'description' => 'Recolhimento do PIS e da COFINS sobre faturamento e importação.',
                        'type' => 'federal',
                        'deadline' => 'Dia 25'
                    ]
                ]
            ];
        }
    }
    
    /**
     * Renderizar calendário
     */
    public function render() {
        // Nome do mês atual
        $monthName = $this->getMonthName($this->currentMonth);
        
        // Iniciar HTML do calendário
        $html = '
        <div class="calendar-container">
            <div class="calendar-header">
                <button type="button" class="btn btn-sm btn-outline-primary calendar-nav" data-month="' . ($this->currentMonth - 1) . '" data-year="' . ($this->currentMonth == 1 ? $this->currentYear - 1 : $this->currentYear) . '">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h4 class="calendar-title">' . $monthName . ' ' . $this->currentYear . '</h4>
                <button type="button" class="btn btn-sm btn-outline-primary calendar-nav" data-month="' . ($this->currentMonth + 1) . '" data-year="' . ($this->currentMonth == 12 ? $this->currentYear + 1 : $this->currentYear) . '">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="calendar-grid">
                <div class="calendar-weekdays">
                    <div>Dom</div>
                    <div>Seg</div>
                    <div>Ter</div>
                    <div>Qua</div>
                    <div>Qui</div>
                    <div>Sex</div>
                    <div>Sáb</div>
                </div>
                
                <div class="calendar-days">';
        
        // Adicionar células vazias para os dias anteriores ao primeiro dia do mês
        for ($i = 0; $i < $this->firstDayOfWeek; $i++) {
            $html .= '<div class="calendar-day empty"></div>';
        }
        
        // Adicionar dias do mês
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            // Verificar se o dia atual tem obrigações
            $hasObligations = isset($this->obligations[$day]);
            $obligationCount = $hasObligations ? count($this->obligations[$day]) : 0;
            
            // Verificar se é o dia atual
            $isToday = ($day == $this->currentDay && $this->currentMonth == date('n') && $this->currentYear == date('Y'));
            
            // Classe para o dia
            $dayClass = 'calendar-day';
            if ($isToday) {
                $dayClass .= ' today';
            }
            if ($hasObligations) {
                $dayClass .= ' has-obligations';
            }
            
            // Adicionar célula do dia
            $html .= '<div class="' . $dayClass . '" data-day="' . $day . '">';
            $html .= '<span class="day-number">' . $day . '</span>';
            
            // Adicionar obrigações visíveis no dia
            if ($hasObligations) {
                $html .= '<div class="obligation-summary">';
                
                foreach ($this->obligations[$day] as $idx => $obligation) {
                    if ($idx >= 2 && $obligationCount > 3) {
                        // Mostrar apenas "mais X" para dias com muitas obrigações
                        $html .= '<div class="obligation-more">';
                        $html .= '+ ' . ($obligationCount - 2) . ' mais...';
                        $html .= '</div>';
                        break;
                    }
                    
                    // Determinar a classe baseada no tipo
                    $typeClass = $this->getTypeClass($obligation['type']);
                    
                    $html .= '<div class="obligation-mini ' . $typeClass . '" data-bs-toggle="tooltip" title="' . htmlspecialchars($obligation['description']) . '">';
                    $html .= $obligation['title'];
                    $html .= '</div>';
                }
                
                $html .= '</div>';
                
                // Adicionar conteúdo do popup (hidden para ser usado no modal)
                $html .= '<div class="obligation-popup" id="obligations-day-' . $day . '">';
                $html .= '<h5>' . $day . ' de ' . $monthName . ' de ' . $this->currentYear . '</h5>';
                $html .= '<ul class="obligation-list">';
                
                foreach ($this->obligations[$day] as $obligation) {
                    // Determinar a classe baseada no tipo
                    $typeClass = $this->getTypeClass($obligation['type']);
                    
                    $html .= '<li class="obligation-item ' . $typeClass . '">';
                    $html .= '<div class="obligation-title">' . $obligation['title'] . '</div>';
                    $html .= '<div class="obligation-description">' . $obligation['description'] . '</div>';
                    $html .= '<div class="obligation-deadline">' . $obligation['deadline'] . '</div>';
                    $html .= '</li>';
                }
                
                $html .= '</ul>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Adicionar células vazias para completar a última semana
        $lastDay = ($this->firstDayOfWeek + $this->daysInMonth) % 7;
        if ($lastDay > 0) {
            for ($i = $lastDay; $i < 7; $i++) {
                $html .= '<div class="calendar-day empty"></div>';
            }
        }
        
        // Fechar HTML do calendário
        $html .= '
                </div>
            </div>
            
            <div class="calendar-legend">
                <div class="legend-item">
                    <span class="legend-color legend-federal"></span>
                    <span>Federal</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-trabalhista"></span>
                    <span>Trabalhista</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-previdenciario"></span>
                    <span>Previdenciário</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-estadual"></span>
                    <span>Estadual</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-municipal"></span>
                    <span>Municipal</span>
                </div>
            </div>
        </div>
        
        <!-- Modal para exibir detalhes das obrigações -->
        <div class="modal fade" id="obligationModal" tabindex="-1" aria-labelledby="obligationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="obligationModalLabel">Obrigações do Dia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body" id="obligationModalBody">
                        <!-- Conteúdo será preenchido via JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Obter nome do mês
     */
    private function getMonthName($month) {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
        
        return $months[$month];
    }
    
    /**
     * Obter classe CSS baseada no tipo de obrigação
     */
    private function getTypeClass($type) {
        $classes = [
            'federal' => 'obligation-federal',
            'estadual' => 'obligation-estadual',
            'municipal' => 'obligation-municipal',
            'trabalhista' => 'obligation-trabalhista',
            'previdenciario' => 'obligation-previdenciario'
        ];
        
        return isset($classes[$type]) ? $classes[$type] : '';
    }
    
    /**
     * Obter obrigações para um dia específico
     */
    public function getObligationsForDay($day) {
        return isset($this->obligations[$day]) ? $this->obligations[$day] : [];
    }
    
    /**
     * Obter obrigações para um mês
     */
    public function getAllObligations() {
        return $this->obligations;
    }
}
?>