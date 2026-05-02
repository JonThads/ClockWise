# tests/accessibility/helpers.py
import json
import allure
import re
from axe_playwright_python.sync_playwright import Axe

axe = Axe()

# WCAG 2.1 Level A and AA tag filters
WCAG_TAGS = {"wcag2a", "wcag2aa", "wcag21a", "wcag21aa"}


def _format_tags(tags):
    """Extract only the WCAG-relevant tags for display."""
    wcag_relevant = [t for t in tags if t.startswith("wcag") or t.startswith("best")]
    return ", ".join(wcag_relevant) if wcag_relevant else "—"


def _format_violations(violations, label, emoji):
    """Format a list of violations or warnings into readable lines."""
    lines = [f"\n{emoji} {label} ({len(violations)}):"]
    for v in violations:
        lines.extend([
            f"\n  {'─' * 55}",
            f"  Rule ID:     {v['id']}",
            f"  Impact:      {v['impact'].upper()}",
            f"  WCAG Tags:   {_format_tags(v['tags'])}",
            f"  Description: {v['description']}",
            f"  Help:        {v['helpUrl']}",
            f"  Affected Elements ({len(v['nodes'])}):",
        ])
        for node in v["nodes"][:3]:
            lines.append(f"    → {node['html'][:200]}")
            if node.get("failureSummary"):
                lines.append(
                    f"      Fix: {node['failureSummary'][:200]}"
                )
    return lines


def _format_passes(passes):
    """Format passed rules into a clean checklist."""
    lines = [f"\n✓ RULES PASSED ({len(passes)}):"]
    lines.append(f"  {'─' * 55}")
    for p in passes:
        lines.append(
            f"  ✓  [{_format_tags(p['tags'])}]  {p['id']}"
            f"  —  {p['description']}"
        )
    return lines


def _format_incomplete(incomplete):
    """Format incomplete checks — rules axe could not fully verify."""
    if not incomplete:
        return []
    lines = [f"\n⚪ NEEDS MANUAL REVIEW ({len(incomplete)}):"]
    lines.append(f"  {'─' * 55}")
    lines.append(
        "  These rules could not be automatically verified "
        "and require manual testing."
    )
    for item in incomplete:
        lines.append(
            f"  ?  [{_format_tags(item['tags'])}]  {item['id']}"
            f"  —  {item['description']}"
        )
    return lines


def run_axe_scan(page, page_label, fail_on_impact=("critical", "serious")):
    """
    Injects axe-core into the current page and runs a WCAG scan.

    Attaches to Allure:
      1. Raw JSON   — full axe-core response
      2. Violations — what failed and why, with affected elements
      3. Passes     — every WCAG rule that was checked and passed
      4. Incomplete — rules that need manual verification
      5. Summary    — one-line scorecard

    Raises AssertionError if violations at or above fail_on_impact exist.
    """
    results = axe.run(page)
    response = results.response

    # ── Extract all four result categories ────────────────────────────────────
    all_violations  = response.get("violations",   [])
    all_passes      = response.get("passes",       [])
    all_incomplete  = response.get("incomplete",   [])
    all_inapplicable= response.get("inapplicable", [])

    # Filter everything to WCAG rules only
    wcag_violations  = [
        v for v in all_violations
        if any(tag in v["tags"] for tag in WCAG_TAGS)
    ]
    wcag_passes      = [
        p for p in all_passes
        if any(tag in p["tags"] for tag in WCAG_TAGS)
    ]
    wcag_incomplete  = [
        i for i in all_incomplete
        if any(tag in i["tags"] for tag in WCAG_TAGS)
    ]

    blocking = [
        v for v in wcag_violations
        if v["impact"] in fail_on_impact
    ]
    warnings = [
        v for v in wcag_violations
        if v["impact"] not in fail_on_impact
    ]

    # ── Attachment 1: Raw JSON ─────────────────────────────────────────────────
    allure.attach(
        json.dumps(response, indent=2, default=str),
        name="📄 axe-core Raw JSON",
        attachment_type=allure.attachment_type.JSON
    )

    # ── Attachment 2: Violations (if any) ─────────────────────────────────────
    if wcag_violations:
        violation_lines = [
            f"Page: {page_label}",
            f"{'=' * 60}",
        ]
        if blocking:
            violation_lines += _format_violations(
                blocking,
                "BLOCKING VIOLATIONS — will fail the test",
                "🔴"
            )
        if warnings:
            violation_lines += _format_violations(
                warnings,
                "WARNINGS — non-blocking",
                "🟡"
            )

        allure.attach(
            "\n".join(violation_lines),
            name="🔴 WCAG Violations",
            attachment_type=allure.attachment_type.TEXT
        )

    # ── Attachment 3: Passed Rules ─────────────────────────────────────────────
    if wcag_passes:
        pass_lines = [
            f"Page: {page_label}",
            f"{'=' * 60}",
            f"The following WCAG 2.1 AA rules were checked "
            f"and all elements passed:",
        ]
        pass_lines += _format_passes(wcag_passes)

        allure.attach(
            "\n".join(pass_lines),
            name="✅ WCAG Rules Passed",
            attachment_type=allure.attachment_type.TEXT
        )

    # ── Attachment 4: Incomplete / Needs Manual Review ─────────────────────────
    if wcag_incomplete:
        incomplete_lines = [
            f"Page: {page_label}",
            f"{'=' * 60}",
            "axe-core could not automatically verify these rules.",
            "They require manual testing with a screen reader or keyboard.",
        ]
        incomplete_lines += _format_incomplete(wcag_incomplete)

        allure.attach(
            "\n".join(incomplete_lines),
            name="⚪ Needs Manual Review",
            attachment_type=allure.attachment_type.TEXT
        )

    # ── Attachment 5: Scorecard Summary ───────────────────────────────────────
    total_checked = len(wcag_violations) + len(wcag_passes) + len(wcag_incomplete)

    scorecard = "\n".join([
        f"{'=' * 60}",
        f"  WCAG 2.1 AA ACCESSIBILITY SCAN SCORECARD",
        f"{'=' * 60}",
        f"  Page:              {page_label}",
        f"  URL:               {response.get('url', 'N/A')}",
        f"  axe-core version:  {response.get('testEngine', {}).get('version', 'N/A')}",
        f"{'─' * 60}",
        f"  🔴 Violations:     {len(wcag_violations)} "
        f"({len(blocking)} blocking, {len(warnings)} warnings)",
        f"  ✅ Passed Rules:   {len(wcag_passes)}",
        f"  ⚪ Manual Review:  {len(wcag_incomplete)}",
        f"  ➖ Not Applicable: {len(all_inapplicable)}",
        f"{'─' * 60}",
        f"  Total Rules Checked: {total_checked}",
        f"  Result: {'❌ FAIL' if blocking else '✅ PASS'}",
        f"{'=' * 60}",
    ])

    allure.attach(
        scorecard,
        name="📊 Scan Scorecard",
        attachment_type=allure.attachment_type.TEXT
    )

    # ── Print scorecard + violations to terminal ───────────────────────────────
    print(f"\n{scorecard}")

    if wcag_violations:
        violation_terminal = _format_violations(
            wcag_violations,
            "WCAG VIOLATIONS FOUND",
            "🔴"
        )
        print("\n".join(violation_terminal))

    if wcag_passes:
        print(f"\n✅ {len(wcag_passes)} WCAG rules passed — "
              f"see Allure '✅ WCAG Rules Passed' attachment for details.")

    if wcag_incomplete:
        print(f"\n⚪ {len(wcag_incomplete)} rules need manual review — "
              f"see Allure '⚪ Needs Manual Review' attachment.")

    # ── Assert ────────────────────────────────────────────────────────────────
    assert len(blocking) == 0, (
        f"\n{len(blocking)} blocking WCAG violation(s) found on '{page_label}'.\n"
        f"See Allure attachments for full details.\n\n" +
        "\n".join([
            f"  [{v['impact'].upper()}] {v['id']}: {v['description']}"
            for v in blocking
        ])
    )

def login_as_admin(page, base_url, credentials):
    """
    Logs in as admin using credentials.json.
    Matches the exact E2E login pattern using get_by_role.
    """
    admin = credentials["admin"]
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.get_by_role("textbox", name="Username").fill(admin["username"])
    page.get_by_role("textbox", name="Password").fill(admin["password"])
    page.get_by_role("button", name="Log In").click()
    page.wait_for_url(
        re.compile(r"admin-dashboard\.php"),
        wait_until="domcontentloaded"
    )


def login_as_user(page, base_url, credentials_key, credentials):
    """
    Logs in as a named user from credentials.json.
    Matches the exact E2E login pattern using get_by_role.
    """
    user = credentials[credentials_key]
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.get_by_role("textbox", name="Username").fill(user["username"])
    page.get_by_role("textbox", name="Password").fill(user["password"])
    page.get_by_role("button", name="Log In").click()
    page.wait_for_url(
        re.compile(r"user-dashboard\.php"),
        wait_until="domcontentloaded"
    )