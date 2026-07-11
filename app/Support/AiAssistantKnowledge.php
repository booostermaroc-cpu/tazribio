<?php

namespace App\Support;

final class AiAssistantKnowledge
{
    /** @return array<string, array{label: string, keywords: list<string>, response: string}> */
    public static function topics(): array
    {
        return [
            'dashboard' => [
                'label' => 'لوحة التحكم',
                'keywords' => ['dashboard', 'tableau', 'bord', 'لوحة', 'تحكم', 'statistique', 'إحصائيات'],
                'response' => <<<'TXT'
لوحة التحكم هي الصفحة اللي كتشوف فيها كلشي دفعة وحدة: شحال عندك من colis، شحال ت livré، شحال رجع، CA، profit، والـ colis اللي واقفين عند Ameex.

مثال: إلا بغيتي تعرف شحال عندك colis livré هاد الشهر، دخل للـ Dashboard وشوف الكارت الخضر "Livrées" — كيعطيك العدد والتطور مقارنة بالشهر اللي فات.
TXT,
            ],
            'orders' => [
                'label' => 'الطلبات / Colis',
                'keywords' => ['colis', 'commande', 'order', 'طلب', 'طرد', 'confirmation', 'whatsapp'],
                'response' => <<<'TXT'
قسم Colis هو القلب ديال النظام. من هنا كتسجل colis جديد، كتأكد مع الكليان، كتتابع الحالة (nouvelle → confirmée → expédiée → livrée).

مثال: colis جديد من Facebook — دخل Colis → Créer، عمر اسم الكليان والتيليفون والمدينة والمنتجات. من بعد كليكي "Contacter WhatsApp" باش ترسل للكليان رسالة التأكيد بالدارجة، ومن بعد Confirmé إلا وافق.
TXT,
            ],
            'products' => [
                'label' => 'المنتجات',
                'keywords' => ['produit', 'product', 'sku', 'stock', 'منتج', 'منتجات', 'ameex reference'],
                'response' => <<<'TXT'
Produits كتدير فيه المنتجات ديالك: السمية، SKU داخلي، Réf Ameex، الثمن، والستوك.

مثال: منتج "Huile d'argan 100ml" — SKU: ARG-100، Réf Ameex: 21820-0-31469-4899-XX (من tableau Ameex). هاد Réf ضرورية باش الإرسال لـ Ameex يخدم مزيان.
TXT,
            ],
            'clients' => [
                'label' => 'العملاء',
                'keywords' => ['client', 'كليان', 'عميل', 'عملاء', 'téléphone', 'هاتف', 'ville'],
                'response' => <<<'TXT'
Clients فيه لائحة الكليان ديالك: الاسم، التيليفون، المدينة، العنوان. كل colis مربوط بكليان.

مثال: الكليان "فاطمة الزهراء" بـ 0612345678 من الدار البيضاء — تقدر تلقاه مباشرة فـ Colis ملي كتختار "client existant" ولا تزيدو فـ Clients.
TXT,
            ],
            'shipments' => [
                'label' => 'الشحنات / Commandes Ameex',
                'keywords' => ['shipment', 'commande', 'ameex', 'tracking', 'شحن', 'تتبع', 'livraison', 'bl'],
                'response' => <<<'TXT'
Commandes (Shipments) هي colis اللي تسيفطو لـ Ameex. من هنا كترسل colis، كتجيب tracking، كتطبع BL، وكتعاود الإرسال.

مثال: colis confirmé — دخل Commandes → Envoyer à Ameex. إلا رجع tracking "AMEEX-12345"، تقدر Refresh tracking وطبع Bon de livraison PDF.
TXT,
            ],
            'delivery_companies' => [
                'label' => 'شركات التوصيل',
                'keywords' => ['transporteur', 'ameex', 'api', 'c-api', 'شركة', 'توصيل', 'hub'],
                'response' => <<<'TXT'
Transporteurs فيه إعداد Ameex: C-Api-Id، C-Api-Key، business ID، hub AGADIR. من هنا كتزامن المدن والحالات.

مثال: أول مرة — دخل Transporteurs → Ameex → Synchronisation Ameex → Synchroniser villes + statuts. من بعد Sync expéditeurs وختار hub=17 (AGADIR).
TXT,
            ],
            'pickup_requests' => [
                'label' => 'طلبات الاستلام',
                'keywords' => ['pickup', 'enlèvement', 'ramassage', 'استلام', 'collecte', 'ville'],
                'response' => <<<'TXT'
Demandes enlèvement كتطلب من Ameex يجيو ياخدو colis من عندك. خاصك تختار المدينة من اللائحة المتزامنة.

مثال: 10 colis جاهزين فـ Agadir — Créer demande enlèvement، اختار ville "Agadir" من القائمة (ماشي كتابة يدوية)، عمر العدد والعنوان، وصيفط.
TXT,
            ],
            'return_bons' => [
                'label' => 'أذونات الإرجاع',
                'keywords' => ['retour', 'return', 'scan', 'إرجاع', 'bon', 'qr', 'barcode'],
                'response' => <<<'TXT'
Bons de retour كيتعاملو مع colis اللي رجعو من Ameex. تقدر تسكانيهم بالـ QR/barcode.

مثال: livreur رجع colis — دخل Scan retour (فـ Ventes)، سكان الكود، النظام كيسجل bon de retour تلقائياً ويحدث الحالة.
TXT,
            ],
            'stock' => [
                'label' => 'المخزون',
                'keywords' => ['stock', 'entrepôt', 'warehouse', 'mouvement', 'مخزون', 'مستودع', 'quantité'],
                'response' => <<<'TXT'
Stock: Entrepôts + Mouvements stock. الستوك كيتنقص ملي colis يتأكد (confirmé) وكيزيد ملي كيدخل merchandise.

مثال: 50 unités "Huile argan" — شوف Stock actuel فـ Produits. ملي confirmé colis بـ 2 وحدة، الستوك يولي 48 تلقائياً. الحركات كاينين فـ Mouvements stock.
TXT,
            ],
            'users' => [
                'label' => 'المستخدمون',
                'keywords' => ['utilisateur', 'user', 'agent', 'rôle', 'permission', 'مستخدم', 'صلاحية', 'pages'],
                'response' => <<<'TXT'
Utilisateurs كتدير agents، managers، livreurs… وكتعطيهم Pages accessibles (شنو يشوفو فـ sidebar).

مثال: agent confirmation — Créer utilisateur، rôle Agent، صحح Colis + Clients + Dashboard. Manager يشوف كلشي ما عدا delete utilisateurs.
TXT,
            ],
            'settings' => [
                'label' => 'الإعدادات',
                'keywords' => ['paramètre', 'setting', 'logo', 'frais', 'commission', 'profit', 'إعداد', 'إعدادات'],
                'response' => <<<'TXT'
Paramètres: اسم الشركة، logo، frais livraison par défaut، prefixes colis/facture، profit manuel/auto، commissions agents.

مثال: frais livraison 35 MAD لكل colis — دخل Paramètres → Frais de livraison par défaut = 35. كل colis جديد غادي يجي بـ 35 MAD تلقائياً.
TXT,
            ],
            'complaints' => [
                'label' => 'الشكايات',
                'keywords' => ['réclamation', 'complaint', 'شكاية', 'مشكل', 'عميل'],
                'response' => <<<'TXT'
Réclamations كتسجل مشاكل الكليان: colis متأخر، منتج ناقص، خدمة… وكتتابع الحالة (ouverte → en cours → résolue).

مثال: كليان هضر أن colis وصل مكسور — Créer réclamation، ربطها بالـ colis، عين agent، ومن بعد Résolue ملي تصلح المشكل.
TXT,
            ],
            'finance' => [
                'label' => 'المالية',
                'keywords' => ['facture', 'invoice', 'dépense', 'paiement', 'finance', 'مالية', 'فاتورة', 'مصاريف'],
                'response' => <<<'TXT'
Finance: Factures، Planification paiements، Dépenses. كتشوف revenue، commissions agents، ومصاريف الشركة.

مثال: دفعت loyer 5000 MAD — دخل Dépenses → Créer، catégorie Loyer، المبلغ 5000. كيبان فـ Dashboard finance widget.
TXT,
            ],
            'confirmation' => [
                'label' => 'تتبع التأكيد',
                'keywords' => ['confirmation', 'suivi', 'agent', 'تأكيد', 'whatsapp', 'appel', 'refus'],
                'response' => <<<'TXT'
Suivi confirmation كيعطيك إحصائيات agents: شحال confirmé، شحال refus، clicks WhatsApp/appel.

مثال: بغيتي تشوف agent "يوسف" شحال colis confirmé — دخل Suivi confirmation، شوف الجدول: colis confirmés، refus، total clicks.
TXT,
            ],
            'reviews' => [
                'label' => 'آراء العملاء',
                'keywords' => ['avis', 'review', 'note', 'rating', 'رأي', 'تقييم', 'نجوم'],
                'response' => <<<'TXT'
Avis clients: من colis livré كتسيفط lien avis بالواتساب، الكليان كيعمر note produit + service.

مثال: colis ORD-0042 livré — من View colis كليكي "Envoyer lien avis". الكليان يعمر 5/5 وكيبان فـ Avis clients.
TXT,
            ],
        ];
    }

    /** @return array{label: string, keywords: list<string>, response: string}|null */
    public static function find(string $topicId): ?array
    {
        return static::topics()[$topicId] ?? null;
    }

    public static function welcome(): string
    {
        return <<<'TXT'
السلام عليكم! أنا مساعد Tazri Bio. اختار وحدة من النظام من الأزرار لتحت، ولا كتب سؤالك (مثلاً: colis، stock، ameex…) وغادي نجاوبك بالدارجة مع مثال عملي.
TXT;
    }

    public static function fallback(): string
    {
        return <<<'TXT'
ما لقيتش جواب محدد على هاد السؤال. جرب تختار وحدة من الأزرار: Colis، Produits، Ameex، Stock، Paramètres… ولا عاود صيغ السؤال بكلمات بحال "tracking"، "confirmation"، "retour".
TXT;
    }

    /** @return array{label: string, response: string}|null */
    public static function matchQuestion(string $question): ?array
    {
        $question = mb_strtolower(trim($question));

        if ($question === '') {
            return null;
        }

        foreach (static::topics() as $topic) {
            foreach ($topic['keywords'] as $keyword) {
                if (str_contains($question, mb_strtolower($keyword))) {
                    return [
                        'label' => $topic['label'],
                        'response' => $topic['response'],
                    ];
                }
            }
        }

        return null;
    }
}
