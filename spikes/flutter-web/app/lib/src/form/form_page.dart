import 'package:flutter/material.dart';
import 'package:flutter/semantics.dart';

import '../../l10n/app_localizations.dart';

/// Arabic text input, a list and an interactive button, all exposed to screen readers.
class FormPage extends StatefulWidget {
  const FormPage({super.key});

  @override
  State<FormPage> createState() => _FormPageState();
}

class _FormPageState extends State<FormPage> {
  int _count = 0;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Semantics(
      role: SemanticsRole.form,
      child: ListView(
        padding: const EdgeInsetsDirectional.all(16),
        children: [
          Semantics(
            key: const ValueKey('form-title'),
            header: true,
            headingLevel: 1,
            child: Text(l10n.formTitle, style: theme.textTheme.headlineSmall),
          ),
          const SizedBox(height: 16),
          TextField(
            key: const ValueKey('name-field'),
            textDirection: TextDirection.rtl,
            decoration: InputDecoration(
              labelText: l10n.nameLabel,
              hintText: l10n.nameHint,
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            key: const ValueKey('notes-field'),
            minLines: 3,
            maxLines: 6,
            decoration: InputDecoration(
              labelText: l10n.notesLabel,
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              FilledButton(
                key: const ValueKey('counter-button'),
                style: FilledButton.styleFrom(minimumSize: const Size(120, 48)),
                onPressed: () => setState(() => _count++),
                child: Text(l10n.counterButton),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Semantics(
                  liveRegion: true,
                  child: Text(l10n.counterValue(_count)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Semantics(
            header: true,
            headingLevel: 2,
            child: Text(l10n.listTitle, style: theme.textTheme.titleMedium),
          ),
          Semantics(
            key: const ValueKey('lesson-list'),
            role: SemanticsRole.list,
            explicitChildNodes: true,
            child: Column(
              children: [
                for (var i = 1; i <= 30; i++)
                  Semantics(
                    key: ValueKey('lesson-$i'),
                    role: SemanticsRole.listItem,
                    container: true,
                    child: ListTile(
                      leading: const Icon(Icons.menu_book_outlined),
                      title: Text(l10n.lessonItem(i)),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
