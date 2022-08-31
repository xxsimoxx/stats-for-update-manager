#!/usr/bin/env bash
set -e

# Change for specific plugin
slug='stats-for-update-manager'
name='Stats for Update Manager'

# Check for required programs
all_found=Y
for prog in wp git hub; do
	if ! [ -x "$(command -v $prog)" ]; then
		echo "Error: required program '$prog' is not installed." >&2
		all_found=N
	fi
done
if [ $all_found = N ]; then
	exit 1
fi

phpfile="${slug}.php"

git status

version=$(wp --skip-plugins --allow-root eval '$v = get_plugin_data( "'${phpfile}'" ); echo $v["Version"];')

echo "Going to release      : v${version}"

read -n 1 -s -r -p "If OK, press any key to continue (CTRL-C to exit)."

echo

git archive -o "../${slug}-${version}.zip" --prefix ${slug}/ HEAD

hub release create -d -a "../${slug}-${version}.zip" -m "${name} ${version}" "${version}"

rm -fr "../${slug}-${version}.zip"