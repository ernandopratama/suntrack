<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RichTextEditorMobileInputTest extends TestCase
{
    public function test_quill_editor_uses_native_editing_and_mobile_keyboard_attributes(): void
    {
        $editor = File::get(resource_path('js/components/RichTextEditor.vue'));

        $this->assertStringContainsString("import Quill from 'quill'", $editor);
        $this->assertStringContainsString("import 'quill/dist/quill.snow.css'", $editor);
        $this->assertStringContainsString('new Quill(editor.value', $editor);
        $this->assertStringContainsString("quill.root.setAttribute('inputmode', 'text')", $editor);
        $this->assertStringContainsString("quill.on('text-change', emitValue)", $editor);
        $this->assertStringNotContainsString('document.execCommand', $editor);
        $this->assertStringNotContainsString('pointerdown', $editor);
        $this->assertStringContainsString('height: 1.75rem;', $editor);
        $this->assertStringContainsString('width: 5.25rem;', $editor);
    }
}
