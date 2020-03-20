# Contributing

When contributing to this repository, if is not a fix for an existing issue or the PR have just few lines, please first discuss the change you wish to make via issue.

## **Did you find a bug?**

* **Do not open up a GitHub issue if the bug is a security vulnerability**. Please send an email to simone@gieffeedizioni.it.

* **Ensure the bug was not already reported** by searching on GitHub.

* If possible, use the relevant bug report templates to create the issue.

## Pull Request Process

1. Check that relative documentation is changed accordingly to new functions.
1. Comment your code as much as possible.
1. Check your code against [code standard](#cs).

## <a name="cs"></a>Code standard

We are not enforcing a precise code standard, but hope your contributes will follow some simple rules.

1. Use explicit variable names.
1. Make your code readable.
1. Don't deep-nest with conditionals.
1. Indent using tabs, not spaces.
1. Keep your code similar to the code you are contributing to.
1. Don't `eval()`.
1. JavaScript and CSS must not be minified or obfuscated.
1. Code must be compatible with ClassicPress from 1.0.0 to the latest release, PHP from 5.6 to 7.4.

If you want to get very close to how I standardize my code, look at my
<details><summary>phpcs checks</summary>

<p>

```xml

<?xml version="1.0" encoding="UTF-8"?>
<ruleset name="xxsimoxx-rules">
	<description>
		Rules for my PHP code.
	</description> 

	<rule ref="SlevomatCodingStandard.Arrays.TrailingArrayComma"/>
	<rule ref="SlevomatCodingStandard.ControlStructures.DisallowYodaComparison"/>
	
	<rule ref="Generic.Files.LineEndings"/>
	<rule ref="Generic.Formatting.DisallowMultipleStatements"/>
	<rule ref="Generic.Functions.OpeningFunctionBraceKernighanRitchie"/>
	<rule ref="Generic.Functions.FunctionCallArgumentSpacing.NoSpaceAfterComma"/>
	<rule ref="Generic.Functions.FunctionCallArgumentSpacing.SpaceBeforeComma"/>
	<rule ref="Generic.Metrics.NestingLevel"/>
	<rule ref="Generic.Metrics.CyclomaticComplexity"/>
	<rule ref="Generic.NamingConventions.UpperCaseConstantName"/>
	<rule ref="Generic.PHP.DeprecatedFunctions"/>
	<rule ref="Generic.PHP.ForbiddenFunctions"/>
	<rule ref="Generic.PHP.LowerCaseConstant"/>
	<rule ref="Generic.PHP.NoSilencedErrors"/>
	<rule ref="Generic.Strings.UnnecessaryStringConcat"/>
	<rule ref="Generic.WhiteSpace.DisallowSpaceIndent"/>
	<rule ref="Generic.ControlStructures.InlineControlStructure"/>

	<rule ref="PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket" />
	<rule ref="PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket" />
	<rule ref="PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket" />
		
	<rule ref="PSR2.ControlStructures.ControlStructureSpacing" />
	<rule ref="PSR2.ControlStructures.ElseIfDeclaration"/>
<rule ref="PEAR.ControlStructures.ControlSignature.Found"/>
	<rule ref="Squiz.PHP.CommentedOutCode"/>
	<rule ref="Squiz.PHP.EmbeddedPhp"/>
	<rule ref="Squiz.PHP.Eval"/>
	<rule ref="Squiz.PHP.NonExecutableCode"/>
	<rule ref="Squiz.PHP.LowercasePHPFunctions"/>
	<rule ref="Squiz.WhiteSpace.ScopeClosingBrace"/>
	<rule ref="Squiz.WhiteSpace.SuperfluousWhitespace"/>
	<rule ref="Squiz.WhiteSpace.CastSpacing"/>
	<rule ref="Squiz.WhiteSpace.LanguageConstructSpacing"/>
	<rule ref="Squiz.WhiteSpace.ObjectOperatorSpacing"/>
	<rule ref="Squiz.WhiteSpace.SemicolonSpacing"/>
	<rule ref="Squiz.WhiteSpace.ObjectOperatorSpacing">
		<properties>
			<property name="ignoreNewlines" value="true" />
		</properties>
	</rule>
	<rule ref="Squiz.WhiteSpace.OperatorSpacing">
		<properties>
			<property name="ignoreNewlines" value="true" />
		</properties>
	</rule>
	<rule ref="Squiz.ControlStructures.ForEachLoopDeclaration"/>
	<rule ref="Squiz.ControlStructures.ForLoopDeclaration"/>
	<rule ref="Squiz.ControlStructures.LowercaseDeclaration"/>
	<rule ref="Squiz.Strings.ConcatenationSpacing" />
	<rule ref="Squiz.Strings.DoubleQuoteUsage"/>
	<rule ref="Squiz.Strings.DoubleQuoteUsage.ContainsVar">
		<severity>0</severity>
	</rule>
	<rule ref="Squiz.Functions.FunctionDeclarationArgumentSpacing"/>
	<rule ref="Squiz.Functions.FunctionDeclarationArgumentSpacing">
		<properties>
			<property name="equalsSpacing" value="1" />
		</properties>
	</rule>
</ruleset>
```

</p>
</details>
