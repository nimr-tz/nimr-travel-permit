<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The user form builds its whole Alpine component inside a single x-data="…"
 * HTML attribute. A literal double quote anywhere in that expression — even
 * inside a // comment — closes the attribute early, and the first ">" after it
 * (the one in any "=>") closes the <section> tag, dumping the rest of the
 * component onto the page as text and leaving every dropdown unpopulated.
 * Guard the rendered markup, not the source, so any future edit is covered.
 */
class UserFormMarkupTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:\DOMXPath,1:string} */
    private function renderForm(string $uri): array
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $html = $this->actingAs($admin)->get($uri)->assertOk()->getContent();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return [new \DOMXPath($doc), $html];
    }

    public function test_the_alpine_component_does_not_leak_into_the_page(): void
    {
        [$xp] = $this->renderForm('/users/create');

        foreach ($xp->query('//text()') as $text) {
            $this->assertStringNotContainsString('supervisor.unit_id', $text->nodeValue,
                'Alpine expression leaked into the DOM as visible text — an unescaped " closed the x-data attribute.');
            $this->assertStringNotContainsString('supervisorApplies', $text->nodeValue,
                'Alpine expression leaked into the DOM as visible text — an unescaped " closed the x-data attribute.');
        }
    }

    public function test_the_org_section_keeps_a_complete_x_data_and_x_init(): void
    {
        [$xp] = $this->renderForm('/users/create');

        $section = $xp->query('//section[@x-data]')->item(0);
        $this->assertNotNull($section, 'organisational-structure section lost its x-data');

        $xData = trim($section->getAttribute('x-data'));
        $this->assertStringEndsWith('}', $xData, 'x-data was truncated mid-expression');
        $this->assertStringContainsString('rolesForUnitType', $xData);
        $this->assertStringContainsString('syncSupervisor', $xData);
        $this->assertNotSame('', $section->getAttribute('x-init'), 'x-init was swallowed by a broken x-data');
    }

    public function test_the_role_and_unit_controls_render(): void
    {
        [$xp] = $this->renderForm('/users/create');

        $this->assertSame(1, $xp->query('//select[@name="role"]')->length);
        $this->assertSame(1, $xp->query('//select[@name="role"]/template[@x-for]')->length,
            'the role <template x-for> is missing — the dropdown would render empty');
        $this->assertSame(1, $xp->query('//select[@name="unit_id"]')->length);
    }
}
