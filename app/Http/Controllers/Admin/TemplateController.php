<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = MessageTemplate::with('createdBy')->orderBy('category')->orderBy('name')->get();
        $categories = ['exam_reminder', 'visa_material', 'welcome', 'payment', 'followup'];

        return view('admin.templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        $categories = ['exam_reminder', 'visa_material', 'welcome', 'payment', 'followup'];
        return view('admin.templates.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|in:exam_reminder,visa_material,welcome,payment,followup',
            'body'     => 'required|string|max:10000',
        ]);

        MessageTemplate::create([
            'name'       => $request->name,
            'category'   => $request->category,
            'body'       => $request->body,
            'active'     => true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.templates.index')->with('success', 'Template created.');
    }

    public function edit(MessageTemplate $template)
    {
        $categories = ['exam_reminder', 'visa_material', 'welcome', 'payment', 'followup'];
        return view('admin.templates.edit', compact('template', 'categories'));
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|in:exam_reminder,visa_material,welcome,payment,followup',
            'body'     => 'required|string|max:10000',
        ]);

        $template->update($request->only('name', 'category', 'body'));

        return redirect()->route('admin.templates.index')->with('success', 'Template updated.');
    }

    public function toggle(MessageTemplate $template)
    {
        $template->update(['active' => !$template->active]);
        return back()->with('success', 'Template ' . ($template->active ? 'activated' : 'deactivated') . '.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }
}
