<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class DocumentTextExtractor
{
    /**
     * @param  array<int, array{type: string, path: string, original_name: string}>  $attachments
     * @return array<int, array{
     *     type: string,
     *     label: string,
     *     kind: 'text'|'image',
     *     text: string,
     *     path: string|null,
     *     mime: string|null,
     *     original_name: string
     * }>
     */
    public function prepareAttachments(array $attachments): array
    {
        $prepared = [];

        foreach ($attachments as $attachment) {
            $absolutePath = Storage::disk('local')->path($attachment['path']);
            $label = $this->labelForType($attachment['type']);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            try {
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $prepared[] = [
                        'type' => $attachment['type'],
                        'label' => $label,
                        'kind' => 'image',
                        'text' => '',
                        'path' => $absolutePath,
                        'mime' => mime_content_type($absolutePath) ?: 'image/jpeg',
                        'original_name' => $attachment['original_name'],
                    ];

                    continue;
                }

                $text = $this->extract($absolutePath);

                // PDF escaneado: pouco texto → trataremos como imagem se possível, senão mantém aviso.
                if ($extension === 'pdf' && mb_strlen(trim($text)) < 80) {
                    $prepared[] = [
                        'type' => $attachment['type'],
                        'label' => $label,
                        'kind' => 'text',
                        'text' => '[PDF sem texto selecionável — possível documento escaneado. Extração depende de OCR da IA se houver páginas convertidas; conteúdo textual não foi obtido automaticamente.]',
                        'path' => $absolutePath,
                        'mime' => 'application/pdf',
                        'original_name' => $attachment['original_name'],
                    ];

                    continue;
                }

                $prepared[] = [
                    'type' => $attachment['type'],
                    'label' => $label,
                    'kind' => 'text',
                    'text' => $this->truncate($text ?: '[Não foi possível extrair texto deste arquivo.]'),
                    'path' => null,
                    'mime' => null,
                    'original_name' => $attachment['original_name'],
                ];
            } catch (Throwable $e) {
                Log::warning('Falha ao preparar anexo', [
                    'type' => $attachment['type'],
                    'path' => $attachment['path'],
                    'error' => $e->getMessage(),
                ]);

                $prepared[] = [
                    'type' => $attachment['type'],
                    'label' => $label,
                    'kind' => 'text',
                    'text' => '[Falha na leitura do arquivo: '.$e->getMessage().']',
                    'path' => null,
                    'mime' => null,
                    'original_name' => $attachment['original_name'],
                ];
            }
        }

        return $prepared;
    }

    /**
     * Compacta imagem para envio à API de visão (reduz tamanho e evita estouro de payload).
     *
     * @return array{base64: string, mime: string}|null
     */
    public function compressImageForVision(string $absolutePath, string $mime): ?array
    {
        if (! is_file($absolutePath) || ! extension_loaded('gd')) {
            if (! is_file($absolutePath)) {
                return null;
            }

            return [
                'base64' => base64_encode((string) file_get_contents($absolutePath)),
                'mime' => $mime,
            ];
        }

        $image = match (strtolower($mime)) {
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => @imagecreatefromjpeg($absolutePath),
        };

        if ($image === false) {
            $image = @imagecreatefromstring((string) file_get_contents($absolutePath));
        }

        if ($image === false) {
            return [
                'base64' => base64_encode((string) file_get_contents($absolutePath)),
                'mime' => $mime,
            ];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxSide = 1600;

        if ($width > $maxSide || $height > $maxSide) {
            $scale = $maxSide / max($width, $height);
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagejpeg($image, null, 72);
        $binary = ob_get_clean();
        imagedestroy($image);

        if ($binary === false || $binary === '') {
            return null;
        }

        return [
            'base64' => base64_encode($binary),
            'mime' => 'image/jpeg',
        ];
    }

    public function extract(string $absolutePath): string
    {
        if (! is_file($absolutePath)) {
            return '';
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractPdf($absolutePath),
            'doc', 'docx' => $this->extractWord($absolutePath),
            'txt' => (string) file_get_contents($absolutePath),
            default => '',
        };
    }

    private function extractPdf(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);

        return trim($pdf->getText() ?? '');
    }

    private function extractWord(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->elementToText($element)."\n";
            }
        }

        return trim($text);
    }

    private function elementToText(mixed $element): string
    {
        if (method_exists($element, 'getText')) {
            $value = $element->getText();

            return is_string($value) ? $value : '';
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->elementToText($child);
            }

            return implode(' ', array_filter($parts));
        }

        return '';
    }

    private function labelForType(string $type): string
    {
        return match ($type) {
            'laudo_medico' => 'Laudo médico',
            'avaliacao_neuropsicologica' => 'Avaliação neuropsicológica',
            'relatorio_escolar' => 'Relatório escolar',
            default => $type,
        };
    }

    private function truncate(string $text, int $max = 15000): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max)."\n\n[Texto truncado por limite de tamanho]";
    }
}
