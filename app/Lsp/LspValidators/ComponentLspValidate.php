<?php

namespace App\Lsp\LspValidators;

use App\Dto\BladeComponentData;
use App\Lsp\DiagnosticError;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class ComponentLspValidate extends BaseLspValidator
{
    private $patternSelfClosing = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";


    private $patternOpeningTag = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

    private $patternClosingTag = "/<\/\s*x[-\:][\w\-\:\.]*\s*>/";

    /**
     * @return DiagnosticError[]
     */
    public function getErrors(TextDocumentItem $document): array
    {
        $availableComponents = ['x-slot'];
        $this->store->availableComponents->each(
            function (BladeComponentData $bladeComponentData) use (&$availableComponents) {
                $availableComponents[] = $bladeComponentData->name;
                $availableComponents[] = $bladeComponentData->altName;
            }
        );

        $doc = $document->text;

        $selfClosing = [];
        $opening = [];
        $closing = [];

        preg_match_all($this->patternSelfClosing, $doc, $selfClosing, PREG_OFFSET_CAPTURE);
        preg_match_all($this->patternOpeningTag, $doc, $opening, PREG_OFFSET_CAPTURE);
        preg_match_all($this->patternClosingTag, $doc, $closing, PREG_OFFSET_CAPTURE);

        $errors = [];
        foreach ($selfClosing[1] as $item) {
            if (!in_array('x-' . $item[0], $availableComponents)) {
                $errors[] = new DiagnosticError(
                    error: 'Component not found: ' . $item[0],
                    type: DiagnosticError::TYPE_NOT_EXISTING,
                    componentName: $item[0],
                    startPos: $item[1],
                    endPos: $item[1] + strlen($item[0]),
                    provideAction: true,
                );
            }
        }

        $closingTags = [];

        foreach ($closing[0] as $item) {
            $cleaned = rtrim(ltrim($item[0], '</x-'), '>');
            $closingTags[] = [$cleaned, $item[1]];
            continue;
            /* if (!in_array('x-' . $cleaned, $availableComponents)) { */
            // For now we do not provide this as it may be annoying?
            /* $errors[] = new DiagnosticError( */
            /*     error: 'Component not found: ' . $cleaned, */
            /*     type: DiagnosticError::TYPE_NOT_EXISTING, */
            /*     startPos: $item[1], */
            /*     endPos: $item[1] + strlen($item[0]) */
            /* ); */
            /* } */
        }

        foreach ($opening[1] as $item) {
            $isClosed = false;

            foreach ($closingTags as $key => $closingTag) {
                if ($closingTag[0] === $item[0]) {
                    $isClosed = true;
                    unset($closingTags[$key]);
                    break;
                }
            }

            if (!$isClosed) {
                // Check that it is not a slot.
                if (!str_starts_with($item[0], 'slot')) {
                    $errors[] = new DiagnosticError(
                        error: 'Component not closed: ' . $item[0],
                        type: DiagnosticError::TYPE_UNCLOSED,
                        startPos: $item[1],
                        endPos: $item[1] + strlen($item[0])
                    );
                }
            }

            if (!in_array('x-' . $item[0], $availableComponents)) {
                // Check that it is not a slot.
                if (!str_starts_with($item[0], 'slot')) {
                    $errors[] = new DiagnosticError(
                        error: 'Component not found: ' . $item[0],
                        type: DiagnosticError::TYPE_NOT_EXISTING,
                        componentName: $item[0],
                        startPos: $item[1],
                        endPos: $item[1] + strlen($item[0]),
                        provideAction: true
                    );
                }
            }
        }

        // TODO: Fix regex for opening tags
        // foreach ($closingTags as $closingTag) {
        //     $errors[] = new DiagnosticError(
        //         error: 'Component opening not found: ' . $closingTag[0],
        //         type: DiagnosticError::TYPE_UNOPENED,
        //         startPos: $closingTag[1],
        //         endPos: $closingTag[1] + strlen($closingTag[0])
        //     );
        // }

        return $errors;
    }
}
