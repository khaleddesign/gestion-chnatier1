{{-- resources/views/emails/confirmation-devis.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de votre demande de devis</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        .title {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .label {
            font-weight: bold;
            color: #374151;
        }
        .value {
            color: #6b7280;
        }
        .next-steps {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .next-steps h3 {
            margin-top: 0;
            color: white;
        }
        .contact-info {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        .timeline {
            margin: 20px 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .timeline-number {
            background: #3b82f6;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏗️ {{ config('app.name', 'Gestion Chantiers') }}</div>
            <h1 class="title">Confirmation de votre demande de devis</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->name }}</strong>,</p>
            
            <p>Nous avons bien reçu votre demande de devis et nous vous remercions de votre confiance. Notre équipe va étudier votre projet dans les plus brefs délais.</p>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #374151;">📋 Récapitulatif de votre demande</h3>
                
                <div class="info-row">
                    <span class="label">Type de projet :</span>
                    <span class="value">{{ ucfirst(str_replace('_', ' ', $devis['type_projet'])) }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Budget estimé :</span>
                    <span class="value">
                        @switch($devis['budget_estime'])
                            @case('moins_10k')
                                Moins de 10 000€
                                @break
                            @case('10k_25k')
                                10 000€ - 25 000€
                                @break
                            @case('25k_50k')
                                25 000€ - 50 000€
                                @break
                            @case('50k_100k')
                                50 000€ - 100 000€
                                @break
                            @case('plus_100k')
                                Plus de 100 000€
                                @break
                            @default
                                {{ $devis['budget_estime'] }}
                        @endswitch
                    </span>
                </div>
                
                @if(isset($devis['date_debut_souhaitee']) && $devis['date_debut_souhaitee'])
                    <div class="info-row">
                        <span class="label">Date de début souhaitée :</span>
                        <span class="value">{{ \Carbon\Carbon::parse($devis['date_debut_souhaitee'])->format('d/m/Y') }}</span>
                    </div>
                @endif
                
                <div class="info-row">
                    <span class="label">Délai préféré :</span>
                    <span class="value">{{ ucfirst(str_replace('_', ' ', $devis['delai_prefere'])) }}</span>
                </div>
                
                @if($devis['description'])
                    <div style="margin-top: 15px;">
                        <span class="label">Description du projet :</span>
                        <p style="margin: 10px 0 0 0; color: #6b7280; font-style: italic;">{{ $devis['description'] }}</p>
                    </div>
                @endif
            </div>

            <div class="next-steps">
                <h3>🚀 Prochaines étapes</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-number">1</div>
                        <div>
                            <strong>Étude de votre demande</strong><br>
                            <small>Notre équipe technique analyse votre projet (24-48h)</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">2</div>
                        <div>
                            <strong>Contact de notre commercial</strong><br>
                            <small>Un expert vous contactera pour affiner votre projet</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">3</div>
                        <div>
                            <strong>Visite technique (si nécessaire)</strong><br>
                            <small>Rendez-vous sur site pour un devis précis</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-number">4</div>
                        <div>
                            <strong>Remise du devis détaillé</strong><br>
                            <small>Proposition complète avec planning et tarifs</small>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Récupérer le commercial principal ou assigner par défaut
                $commercial = $user->chantiersClient()->first()?->commercial ?? 
                             \App\Models\User::where('role', 'commercial')->where('active', true)->first();
            @endphp

            @if($commercial)
                <div class="contact-info">
                    <h3 style="margin-top: 0; color: #065f46;">👤 Votre interlocuteur dédié</h3>
                    <p><strong>{{ $commercial->name }}</strong><br>
                    Commercial Expert</p>
                    
                    @if($commercial->telephone)
                        <p>📞 <strong>Téléphone :</strong> {{ $commercial->telephone }}</p>
                    @endif
                    
                    @if($commercial->email)
                        <p>📧 <strong>Email :</strong> {{ $commercial->email }}</p>
                    @endif
                    
                    <p style="margin-bottom: 0;"><em>N'hésitez pas à le contacter directement pour toute question sur votre projet.</em></p>
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('dashboard') }}" class="button">
                    🏠 Accéder à mon espace client
                </a>
            </div>

            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                <h4 style="margin-top: 0; color: #92400e;">💡 Le saviez-vous ?</h4>
                <p style="margin-bottom: 0; color: #92400e;">
                    Vous pouvez suivre l'avancement de votre demande en temps réel depuis votre espace client. 
                    Vous recevrez également des notifications à chaque étape importante.
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Votre partenaire de confiance pour tous vos projets de construction et rénovation</p>
            <p style="margin: 15px 0;">
                📧 <a href="mailto:contact@{{ str_replace(['http://', 'https://'], '', config('app.url')) }}">contact@{{ str_replace(['http://', 'https://'], '', config('app.url')) }}</a> |
                📞 <a href="tel:+33123456789">01 23 45 67 89</a> |
                🌐 <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
            </p>
            <p style="font-size: 12px; color: #9ca3af;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.<br>
                Pour toute question, utilisez les coordonnées ci-dessus.
            </p>
        </div>
    </div>
</body>
</html>