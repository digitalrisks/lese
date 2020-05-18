<?php

namespace DigitalRisks\Lese\Handlers;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use DateTimeInterface;
use DigitalRisks\Lese\Lese;
use DigitalRisks\Lese\StubModel;
use Exception;
use Prooph\EventStore\Async\EventAppearedOnPersistentSubscription;
use Prooph\EventStore\Async\EventStorePersistentSubscription;
use Prooph\EventStore\ResolvedEvent;
use Spatie\EventSourcing\StoredEvent;
use Illuminate\Support\Str;
use Prooph\EventStore\PersistentSubscriptionNakEventAction;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class OnEvent implements EventAppearedOnPersistentSubscription
{
    protected Lese $lese;

    public function __construct(Lese $lese)
    {
        $this->lese = $lese;
    }

    public function __invoke(EventStorePersistentSubscription $subscription, ResolvedEvent $resolvedEvent, ?int $retryCount = null): Promise
    {
        try {
            $storedEvent = $this->lese->recordedEventToStoredEvent($resolvedEvent->event());

            $storedEvent->handle();

            $subscription->acknowledge($resolvedEvent);
        }
        catch (Exception $e) {
            report($e);

            $subscription->fail($resolvedEvent, PersistentSubscriptionNakEventAction::unknown(), $e->getMessage());
        }

        return new Success();
    }
};
