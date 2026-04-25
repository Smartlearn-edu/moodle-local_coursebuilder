<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursebuilder;

defined('MOODLE_INTERNAL') || die();

class output {
    
    public static function render_preview($data_array) {
        $html = '<div class="coursebuilder-preview mt-4 mb-4">';
        $html .= '<h3 class="mb-4">' . get_string('preview_title', 'local_coursebuilder') . '</h3>';
        
        // CSS for the preview to match the mockup
        $html .= '<style>
            .cb-section { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
            .cb-section-header { background-color: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; display: flex; align-items: center; }
            .cb-section-icon { background-color: #e0f2fe; color: #0284c7; width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
            .cb-section-title { font-weight: 600; font-size: 1.25rem; color: #1e293b; margin: 0; }
            .cb-section-intro { padding: 10px 20px 0 20px; font-size: 0.95rem; color: #475569; }
            .cb-activities { padding: 10px 20px 20px 20px; }
            .cb-activity { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
            .cb-activity:last-child { border-bottom: none; padding-bottom: 0; }
            .cb-activity-icon { margin-right: 15px; font-size: 1.2rem; }
            .cb-activity-title { font-size: 1rem; color: #0ea5e9; font-weight: 500; }
            .cb-icon-page { color: #38bdf8; }
            .cb-icon-quiz { color: #f43f5e; }
            .cb-icon-assign { color: #f59e0b; }
            .cb-icon-forum { color: #8b5cf6; }
            .cb-icon-default { color: #94a3b8; }
        </style>';
        
        $sections = [];
        $current_section = null;
        
        // Group activities by section
        foreach ($data_array as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $type = trim(strtolower($row['type'] ?? ''));
            $name = trim($row['name'] ?? '');
            
            if (empty($type) || empty($name)) continue;
            
            if ($type === 'section') {
                $sections[] = [
                    'name' => $name,
                    'intro' => $row['intro'] ?? '',
                    'activities' => []
                ];
                $current_section = &$sections[count($sections) - 1];
            } else {
                if ($current_section === null) {
                    // Create a default "General" section if activities appear before any section
                    $sections[] = [
                        'name' => 'General',
                        'intro' => '',
                        'activities' => []
                    ];
                    $current_section = &$sections[count($sections) - 1];
                }
                $current_section['activities'][] = [
                    'type' => $type,
                    'name' => $name
                ];
            }
        }
        
        // Render sections
        foreach ($sections as $section) {
            $html .= '<div class="cb-section">';
            
            // Header
            $html .= '<div class="cb-section-header">';
            $html .= '<div class="cb-section-icon"><i class="fa fa-chevron-down"></i></div>';
            $html .= '<h4 class="cb-section-title">' . htmlspecialchars($section['name']) . '</h4>';
            $html .= '</div>';
            
            // Intro
            if (!empty($section['intro'])) {
                $html .= '<div class="cb-section-intro">' . strip_tags($section['intro']) . '</div>';
            }
            
            // Activities
            if (!empty($section['activities'])) {
                $html .= '<div class="cb-activities">';
                foreach ($section['activities'] as $act) {
                    $icon_class = self::get_icon_class($act['type']);
                    $html .= '<div class="cb-activity">';
                    $html .= '<div class="cb-activity-icon ' . $icon_class[1] . '"><i class="fa ' . $icon_class[0] . '"></i></div>';
                    $html .= '<div class="cb-activity-title">' . htmlspecialchars($act['name']) . '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>'; // end cb-section
        }
        
        $html .= '</div>'; // end coursebuilder-preview
        
        return $html;
    }
    
    private static function get_icon_class($type) {
        $map = [
            'page' => ['fa-file-text-o', 'cb-icon-page'],
            'quiz' => ['fa-check-square-o', 'cb-icon-quiz'],
            'assignment' => ['fa-upload', 'cb-icon-assign'],
            'assign' => ['fa-upload', 'cb-icon-assign'],
            'forum' => ['fa-comments-o', 'cb-icon-forum'],
            'label' => ['fa-tag', 'cb-icon-default'],
        ];
        return $map[$type] ?? ['fa-file-o', 'cb-icon-default'];
    }
}
