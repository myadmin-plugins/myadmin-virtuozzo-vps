{assign var=ram value=$settings['slice_ram'] * $vps_slices}
{assign var=hd value=(($settings['slice_hd'] * $vps_slices) + $settings['additional_hd']) * 1024}
{if in_array($vps_custid, [2773, 8, 2304])}
{assign var=cpuunits value=1500 * 1.5 * $vps_slices}
{assign var=cpulimit value=100 * $vps_slices}
{assign var=cpus value=ceil($vps_slices / 4 * 2)}
{else}
{assign var=cpuunits value=1500 * $vps_slices}
{assign var=cpulimit value=25 * $vps_slices}
{assign var=cpus value=ceil($vps_slices / 4)}
{/if}
function iprogress() {literal}{{/literal}
  curl --connect-timeout 60 --max-time 240 -k -d action=install_progress -d progress=$1 -d server={$vps_id} 'https://myvps2.interserver.net/vps_queue.php' < /dev/null > /dev/null 2>&1;
{literal}}{/literal}
iprogress 10 &
prlctl create {$vps_vzid} --vmtype ct --ostemplate {$template};
iprogress 20 &
prlctl set {$vps_vzid} --swappages 1G --userpasswd root:{$rootpass};
iprogress 30 &
prlctl set {$vps_vzid} --hostname {$hostname};
iprogress 40 &
prlctl set {$vps_vzid} --cpus {$cpus};
iprogress 50 &
prlctl set {$vps_vzid} --device-add net --type routed --ipadd {$vps_ip} --nameserver 8.8.8.8;
iprogress 60 &
prlctl set {$vps_vzid} --onboot yes --memsize {$ram}M;
iprogress 70 &
prlctl set {$vps_vzid} --device-set hdd0 --size {$hd};
iprogress 80 &
ports=" $(prlctl list -a -i |grep "Remote display:.*port=" |sed s#"^.*port=\([0-9]*\) .*$"#"\1"#g) ";
start=5901;
found=0; 
while [ $found -eq 0 ]; do
  if [ "$(echo "$found" | grep " $start ")" = "" ]; then
    found=$start;
  else
    start=$(($start + 1));
  fi;
done;
prlctl set {$vps_vzid} --vnc-mode manual --vnc-port $start --vnc-nopasswd --vnc-address 127.0.0.1;
iprogress 90 &
prlctl start {$vps_vzid};
iprogress 100 &