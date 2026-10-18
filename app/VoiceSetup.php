<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class VoiceSetup
{
    public static function configuration(string $mode,array $input): array
    {
        if ($mode==='direct') return [];
        if ($mode==='stun') return [['urls'=>'stun:stun.cloudflare.com:3478']];
        if ($mode==='advanced') return Chat::validateIce(is_string($input['ice_servers']??null)?$input['ice_servers']:'');
        if ($mode!=='turn') throw new DomainException('Invalid connection option / Ungültige Verbindungsoption.');
        $host=$input['turn_host']??''; $port=filter_var($input['turn_port']??3478,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        $transport=$input['turn_transport']??'udp';
        if (!is_string($host) || strlen($host)>190 || (!filter_var($host,FILTER_VALIDATE_IP) && !preg_match('/^(?=.{1,190}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D',$host)) || !$port || !in_array($transport,['udp','tcp','tls'],true)) throw new DomainException('Invalid TURN host/port / Ungültiger TURN-Host/Port.');
        foreach(['turn_user','turn_password'] as $key) if (!is_string($input[$key]??null) || $input[$key]==='' || strlen($input[$key])>500 || preg_match('/[\x00-\x1f\x7f]/',$input[$key])) throw new DomainException('TURN requires restricted credentials / TURN benötigt eingeschränkte Zugangsdaten.');
        if (filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)) $host='['.$host.']';
        $server=['urls'=>($transport==='tls'?'turns:':'turn:').$host.':'.$port.'?transport='.($transport==='udp'?'udp':'tcp'),'username'=>$input['turn_user'],'credential'=>$input['turn_password']];
        return Chat::validateIce(json_encode([$server],JSON_THROW_ON_ERROR));
    }
    public static function save(App $a): void
    {
        $a->needAdmin(); $mode=$a->text('voice_setup',10);
        $ice=self::configuration($mode,$_POST);
        $a->db->transaction(function() use($a,$mode,$ice):void {
            $a->saveSetting('ice_servers',json_encode($ice,JSON_THROW_ON_ERROR)); $a->saveSetting('voice_setup',$mode);
            foreach(['turn_host','turn_port','turn_transport','turn_user','turn_password'] as $key) if ($mode==='turn') $a->saveSetting($key,(string)($_POST[$key]??($key==='turn_port'?'3478':($key==='turn_transport'?'udp':''))));
        });
    }
}
