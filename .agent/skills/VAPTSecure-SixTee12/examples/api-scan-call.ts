/**
 * VAPTSecure SixTee12 — Example: API Scan Integration
 *
 * Demonstrates how to use the Claude API with the VAPTSecure SixTee12 skill
 * to programmatically generate security interfaces and test suites.
 *
 * Usage:
 *   npm install @anthropic-ai/sdk
 *   ANTHROPIC_API_KEY=your_key npx ts-node examples/api-scan-call.ts
 */

import Anthropic from "@anthropic-ai/sdk";
import fs from "fs";
import path from "path";

const client = new Anthropic({
  apiKey: process.env.ANTHROPIC_API_KEY,
});

// ─── Load the VAPTSecure SixTee12 skill ──────────────────────────────────────

const skillPath = path.resolve(__dirname, "../SKILL.md");
const skill = fs.readFileSync(skillPath, "utf-8");

const SYSTEM_PROMPT = `You are a senior WordPress security engineer and UI developer.
The following skill defines your knowledge base, risk catalogue, and output standards:

${skill}

Always follow the skill instructions precisely. Generate production-ready code only.`;

// ─── Types ────────────────────────────────────────────────────────────────────

interface ScanRequest {
  task: "dashboard" | "tests" | "remediation-report" | "risk-card";
  risks?: string[];           // e.g. ["RISK-001", "RISK-007"] — defaults to all 12
  format?: "react" | "html" | "jest" | "playwright" | "bash" | "markdown";
  targetUrl?: string;
  options?: Record<string, unknown>;
}

interface ScanResult {
  task: string;
  generatedCode: string;
  estimatedTokens: number;
  model: string;
}

// ─── Core scan function ───────────────────────────────────────────────────────

async function runVAPTScan(req: ScanRequest): Promise<ScanResult> {
  let prompt: string;

  switch (req.task) {
    case "dashboard":
      prompt = `Using the VAPTSecure SixTee12 skill, generate a production-ready React dashboard
for ${req.risks ? `these risks: ${req.risks.join(", ")}` : "all 12 VAPT risks"}.
Apply the Google Antigravity aesthetic. Include:
- Live scan simulation with per-card Run Test buttons
- Severity filters (HIGH / MEDIUM / LOW)
- Dynamic security score ring
- Expandable detail drawers with attack scenarios and remediation
${req.targetUrl ? `Target site: ${req.targetUrl}` : ""}`;
      break;

    case "tests":
      prompt = `Using the VAPTSecure SixTee12 skill, generate a complete Jest + TypeScript
verification test suite for ${req.risks ? `these risks: ${req.risks.join(", ")}` : "all 12 VAPT risks"}.
Include beforeAll/afterAll hooks, meaningful assertions, and clear test descriptions.
${req.targetUrl ? `Base URL: process.env.TEST_TARGET_URL (default: "${req.targetUrl}")` : ""}`;
      break;

    case "remediation-report":
      prompt = `Using the VAPTSecure SixTee12 skill, generate a professional HTML remediation report
for a site where these risks are UNPROTECTED: ${(req.risks || ["RISK-001", "RISK-007"]).join(", ")}.
Include: executive summary, risk severity matrix, step-by-step remediation for each risk,
estimated effort, and compliance impact (PCI-DSS, GDPR, NIST CSF).
Format as a printable, standalone HTML document.`;
      break;

    case "risk-card":
      prompt = `Using the VAPTSecure SixTee12 skill, generate a standalone React component
for risk ${req.risks?.[0] || "RISK-007"}.
Show: severity badge with pulsing animation, CVSS arc gauge, attack scenario,
test payloads (code block), remediation panel, and a "Run Test" button.
No external dependencies.`;
      break;

    default:
      throw new Error(`Unknown task: ${req.task}`);
  }

  const response = await client.messages.create({
    model: "claude-sonnet-4-6",
    max_tokens: 8192,
    system: SYSTEM_PROMPT,
    messages: [{ role: "user", content: prompt }],
  });

  const generatedCode = response.content
    .filter((block) => block.type === "text")
    .map((block) => (block as { text: string }).text)
    .join("\n");

  return {
    task: req.task,
    generatedCode,
    estimatedTokens: response.usage.output_tokens,
    model: response.model,
  };
}

// ─── Example: Batch scan for multiple outputs ─────────────────────────────────

async function runBatchScan() {
  console.log("🛡  VAPTSecure SixTee12 — API Scan Example\n");

  const requests: ScanRequest[] = [
    // 1. Full dashboard
    {
      task: "dashboard",
      format: "react",
      targetUrl: "https://example.com",
    },
    // 2. Jest tests for HIGH severity only
    {
      task: "tests",
      risks: ["RISK-001", "RISK-002", "RISK-007"],
      format: "jest",
      targetUrl: "https://example.com",
    },
    // 3. Remediation report for unprotected risks
    {
      task: "remediation-report",
      risks: ["RISK-002", "RISK-007", "RISK-012"],
      format: "markdown",
    },
  ];

  const outputDir = path.resolve(__dirname, "../../output");
  fs.mkdirSync(outputDir, { recursive: true });

  for (const req of requests) {
    console.log(`⟳  Generating: ${req.task}...`);
    try {
      const result = await runVAPTScan(req);

      const ext = req.format === "jest" ? ".test.ts"
        : req.format === "playwright" ? ".spec.ts"
        : req.format === "react" ? ".jsx"
        : req.format === "bash" ? ".sh"
        : req.format === "html" ? ".html"
        : ".md";

      const outFile = path.join(outputDir, `vapt-${req.task}${ext}`);
      fs.writeFileSync(outFile, result.generatedCode, "utf-8");

      console.log(`✅  ${req.task} → ${outFile} (${result.estimatedTokens} tokens)`);
    } catch (err) {
      console.error(`❌  ${req.task} failed:`, err);
    }
  }

  console.log("\n✦  Batch scan complete. Check ./output/");
}

// ─── Streaming variant (for long outputs) ────────────────────────────────────

async function streamDashboard() {
  console.log("🛡  Streaming full dashboard generation...\n");

  const stream = client.messages.stream({
    model: "claude-sonnet-4-6",
    max_tokens: 8192,
    system: SYSTEM_PROMPT,
    messages: [{
      role: "user",
      content: "Using the VAPTSecure SixTee12 skill, generate the full production React dashboard for all 12 VAPT risks.",
    }],
  });

  let output = "";
  for await (const event of stream) {
    if (event.type === "content_block_delta" && event.delta.type === "text_delta") {
      process.stdout.write(event.delta.text);
      output += event.delta.text;
    }
  }

  fs.writeFileSync("vapt-dashboard-streamed.jsx", output);
  console.log("\n\n✦  Saved to vapt-dashboard-streamed.jsx");
}

// ─── Run ──────────────────────────────────────────────────────────────────────

const mode = process.argv[2] || "batch";

if (mode === "stream") {
  streamDashboard().catch(console.error);
} else {
  runBatchScan().catch(console.error);
}
